<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCustomerPayment;
use App\Models\DeliveryPersonCashTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class CollectionPaymentService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected DeliveryPersonCashService $cashService,
        protected StoreEarningsService $storeEarningsService,
        protected StripePaymentService $stripeService,
        protected PayPalPaymentService $paypalService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPaymentState(Booking $booking): array
    {
        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
        $pricing = $booking->getOperationalPricingSummary($this->currencyService, false);
        $totalMinor = (int) ($pricing['final_total_minor'] ?? $pricing['calculated_total_minor'] ?? 0);

        $confirmedPaymentsMinor = (int) BookingCustomerPayment::query()
            ->where('booking_id', $booking->id)
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->sum('amount_minor');

        $awaitingVerificationMinor = (int) BookingCustomerPayment::query()
            ->where('booking_id', $booking->id)
            ->where('status', BookingCustomerPayment::STATUS_AWAITING_VERIFICATION)
            ->sum('amount_minor');

        // Respect pre-existing confirmed advance / gateway payment if not yet tracked in booking_customer_payments
        $preExistingPaidMinor = 0;
        if ($confirmedPaymentsMinor === 0 && $booking->payment_status === 'paid') {
            $preExistingPaidMinor = $totalMinor;
        }

        $totalConfirmedMinor = max($confirmedPaymentsMinor, $preExistingPaidMinor);
        $outstandingMinor = max(0, $totalMinor - $totalConfirmedMinor);

        $isPaymentSatisfied = $outstandingMinor === 0
            || in_array((string) $booking->payment_status, ['paid', 'authorized', 'authorized_for_later_charge'], true);

        return [
            'currency' => $currency,
            'total_minor' => $totalMinor,
            'confirmed_paid_minor' => $totalConfirmedMinor,
            'awaiting_verification_minor' => $awaitingVerificationMinor,
            'outstanding_minor' => $outstandingMinor,
            'has_awaiting_verification' => $awaitingVerificationMinor > 0,
            'is_payment_satisfied' => $isPaymentSatisfied,
            'is_pricing_complete' => (bool) $pricing['is_pricing_complete'],
            'pricing' => $pricing,
        ];
    }

    public function recordCashInHand(
        Booking $booking,
        DeliveryPerson $person,
        int $amountMinor,
        ?string $notes = null,
        ?string $idempotencyKey = null
    ): BookingCustomerPayment {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Cash received amount must be greater than zero.');
        }

        $key = $idempotencyKey ?: ('cash:' . $booking->id . ':' . $person->id . ':' . Str::uuid());

        return DB::transaction(function () use ($booking, $person, $amountMinor, $notes, $key) {
            $existing = BookingCustomerPayment::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($locked->collection_otp_verified_at === null) {
                throw new RuntimeException('Collection OTP must be verified before recording customer payment.');
            }

            $state = $this->getPaymentState($locked);
            if ($state['outstanding_minor'] <= 0) {
                throw new RuntimeException('This booking has no outstanding balance to collect.');
            }
            if ($amountMinor > $state['outstanding_minor']) {
                throw new RuntimeException(
                    'Collected cash cannot exceed the outstanding balance ('
                    . $this->currencyService->formatMinor($state['outstanding_minor'], $state['currency'])
                    . ').'
                );
            }

            $now = now();
            $payment = BookingCustomerPayment::query()->create([
                'booking_id' => $locked->id,
                'delivery_person_id' => $person->id,
                'payment_method' => BookingCustomerPayment::METHOD_CASH_IN_HAND,
                'payment_channel' => BookingCustomerPayment::CHANNEL_CASH,
                'amount_minor' => $amountMinor,
                'currency' => $state['currency'],
                'status' => BookingCustomerPayment::STATUS_CONFIRMED,
                'external_reference' => 'CASH-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
                'idempotency_key' => $key,
                'notes' => $notes ? trim($notes) : null,
                'collected_at' => $now,
            ]);

            $this->cashService->postCashCustodyTransaction(
                person: $person,
                transactionType: DeliveryPersonCashTransaction::TYPE_CUSTOMER_CASH_COLLECTED,
                direction: DeliveryPersonCashTransaction::DIRECTION_CREDIT,
                amountMinor: $amountMinor,
                currency: $state['currency'],
                paymentChannel: DeliveryPersonCashTransaction::CHANNEL_CASH_IN_HAND,
                description: sprintf('Cash in Hand collected for booking %s (%s)', $locked->reference, $payment->external_reference),
                idempotencyKey: 'cash-custody-payment:' . $payment->id,
                bookingId: $locked->id,
                settlementId: null,
                createdByType: 'delivery_person',
                createdById: $person->id
            );

            $newRemaining = $state['outstanding_minor'] - $amountMinor;
            $locked->forceFill([
                'cash_collected_minor' => (int) $locked->cash_collected_minor + $amountMinor,
                'customer_payment_channel' => 'cash_in_hand',
                'payment_status' => $newRemaining === 0 ? 'paid' : 'partially_paid',
                'paid_at' => $newRemaining === 0 ? ($locked->paid_at ?: $now) : $locked->paid_at,
            ])->save();

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'customer_cash_collected',
                'summary' => sprintf(
                    'Delivery Person %s (%s) confirmed Cash in Hand %s for booking %s.',
                    $person->full_name,
                    $person->code,
                    $this->currencyService->formatMinor($amountMinor, $state['currency']),
                    $locked->reference
                ),
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'payment_id' => $payment->id,
                    'amount_minor' => $amountMinor,
                    'remaining_minor' => $newRemaining,
                ],
            ]);

            return $payment;
        });
    }

    public function recordOnlineTransferClaim(
        Booking $booking,
        DeliveryPerson $person,
        int $amountMinor,
        string $externalReference,
        string $receivingAccount,
        ?string $notes = null,
        ?string $idempotencyKey = null
    ): BookingCustomerPayment {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Transfer amount must be greater than zero.');
        }

        $ref = trim($externalReference);
        if ($ref === '') {
            throw new RuntimeException('Transfer reference number is required.');
        }

        $key = $idempotencyKey ?: ('transfer:' . $booking->id . ':' . $person->id . ':' . Str::slug($ref));

        return DB::transaction(function () use ($booking, $person, $amountMinor, $ref, $receivingAccount, $notes, $key) {
            $existing = BookingCustomerPayment::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($locked->collection_otp_verified_at === null) {
                throw new RuntimeException('Collection OTP must be verified before recording customer payment.');
            }

            $state = $this->getPaymentState($locked);
            if ($amountMinor > $state['outstanding_minor']) {
                throw new RuntimeException('Transfer amount cannot exceed the outstanding booking balance.');
            }

            $payment = BookingCustomerPayment::query()->create([
                'booking_id' => $locked->id,
                'delivery_person_id' => $person->id,
                'payment_method' => BookingCustomerPayment::METHOD_ONLINE_TRANSFER,
                'payment_channel' => BookingCustomerPayment::CHANNEL_BANK_TRANSFER,
                'amount_minor' => $amountMinor,
                'currency' => $state['currency'],
                'status' => BookingCustomerPayment::STATUS_AWAITING_VERIFICATION,
                'external_reference' => $ref,
                'receiving_account' => trim($receivingAccount) ?: 'Business Bank Account',
                'idempotency_key' => $key,
                'notes' => $notes ? trim($notes) : null,
                'collected_at' => now(),
            ]);

            if (! in_array($locked->payment_status, ['paid', 'partially_paid'], true)) {
                $locked->forceFill([
                    'payment_status' => 'pending_verification',
                    'customer_payment_channel' => 'online_transfer',
                ])->save();
            }

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'online_transfer_reported',
                'summary' => sprintf(
                    'Delivery Person %s (%s) reported Online Transfer (%s) awaiting admin verification.',
                    $person->full_name,
                    $person->code,
                    $ref
                ),
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'payment_id' => $payment->id,
                    'amount_minor' => $amountMinor,
                    'reference' => $ref,
                ],
            ]);

            return $payment;
        });
    }

    public function verifyOnlineTransfer(
        BookingCustomerPayment $payment,
        int $adminId,
        bool $approve = true,
        ?string $notes = null
    ): BookingCustomerPayment {
        return DB::transaction(function () use ($payment, $adminId, $approve, $notes) {
            /** @var BookingCustomerPayment $lockedPayment */
            $lockedPayment = BookingCustomerPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== BookingCustomerPayment::STATUS_AWAITING_VERIFICATION) {
                throw new RuntimeException('Only transfers awaiting verification can be processed.');
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($lockedPayment->booking_id)->lockForUpdate()->firstOrFail();

            if (! $approve) {
                $lockedPayment->update([
                    'status' => BookingCustomerPayment::STATUS_FAILED,
                    'verified_by_admin_id' => $adminId,
                    'verified_at' => now(),
                    'notes' => trim(($lockedPayment->notes ? $lockedPayment->notes . ' | ' : '') . 'Rejected by admin: ' . ($notes ?: 'Unverified transfer')),
                ]);

                if ($lockedBooking->payment_status === 'pending_verification') {
                    $lockedBooking->forceFill(['payment_status' => 'pending'])->save();
                }

                return $lockedPayment;
            }

            $lockedPayment->update([
                'status' => BookingCustomerPayment::STATUS_CONFIRMED,
                'verified_by_admin_id' => $adminId,
                'verified_at' => now(),
            ]);

            $state = $this->getPaymentState($lockedBooking);
            $lockedBooking->forceFill([
                'payment_status' => $state['outstanding_minor'] === 0 ? 'paid' : 'partially_paid',
                'paid_at' => $state['outstanding_minor'] === 0 ? ($lockedBooking->paid_at ?: now()) : $lockedBooking->paid_at,
                'customer_payment_channel' => 'online_transfer',
            ])->save();

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'action' => 'online_transfer_verified',
                'summary' => "Admin verified Online Transfer {$lockedPayment->external_reference}.",
                'changes' => [
                    'payment_id' => $lockedPayment->id,
                    'admin_id' => $adminId,
                    'amount_minor' => $lockedPayment->amount_minor,
                ],
            ]);

            return $lockedPayment;
        });
    }

    public function initiatePayPalOrder(Booking $booking, DeliveryPerson $person): array
    {
        if ($booking->collection_otp_verified_at === null) {
            throw new RuntimeException('Collection OTP must be verified before initiating PayPal payment.');
        }

        if (! $this->paypalService->isActive()) {
            throw new RuntimeException('PayPal is currently unavailable in payment settings.');
        }

        $state = $this->getPaymentState($booking);
        if ($state['outstanding_minor'] <= 0) {
            throw new RuntimeException('This booking has no outstanding balance.');
        }

        $result = $this->paypalService->createOrder(
            $booking,
            route('booking.paypal.capture', ['booking' => $booking->id]),
            route('delivery-person.bookings.show', $booking)
        );

        if (! empty($result['order_id'])) {
            $booking->forceFill([
                'paypal_order_id' => $result['order_id'],
                'customer_payment_channel' => 'paypal',
            ])->save();
        }

        return $result;
    }

    public function initiateStripeSession(Booking $booking, DeliveryPerson $person): array
    {
        if ($booking->collection_otp_verified_at === null) {
            throw new RuntimeException('Collection OTP must be verified before initiating Stripe payment.');
        }

        $state = $this->getPaymentState($booking);
        if ($state['outstanding_minor'] <= 0) {
            throw new RuntimeException('This booking has no outstanding balance.');
        }

        if (! $this->stripeService->isActive()) {
            throw new RuntimeException('Stripe is currently unavailable in payment settings.');
        }

        $booking->loadMissing('user');
        $intent = $this->stripeService->createPaymentIntent(
            amountMinor: $state['outstanding_minor'],
            currency: $state['currency'],
            booking: $booking
        );

        $booking->forceFill([
            'stripe_payment_intent_id' => $intent['id'] ?? $booking->stripe_payment_intent_id,
            'customer_payment_channel' => 'stripe',
        ])->save();

        return $intent;
    }

    public function finalizeCollectionBooking(Booking $booking, DeliveryPerson $person): Booking
    {
        return DB::transaction(function () use ($booking, $person) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
                throw new RuntimeException('Cancelled bookings cannot be finalized.');
            }

            if ($locked->collection_otp_verified_at === null) {
                throw new RuntimeException('Collection OTP must be verified before finalizing this booking.');
            }

            $pricing = $locked->getOperationalPricingSummary($this->currencyService, true);
            if (! $pricing['is_pricing_complete']) {
                throw new RuntimeException('Operational pricing must be complete before finalizing.');
            }

            $state = $this->getPaymentState($locked);
            if (! $state['is_payment_satisfied']) {
                throw new RuntimeException('Customer payment must be confirmed before finalizing this booking.');
            }

            $stages = is_array($locked->completed_operational_stages) ? $locked->completed_operational_stages : [];
            $nowIso = now()->toIso8601String();
            if ($locked->booking_status === Booking::STATUS_DELIVERY_MAN_ASSIGNED && ! isset($stages[Booking::STATUS_DELIVERY_MAN_ASSIGNED])) {
                $stages[Booking::STATUS_DELIVERY_MAN_ASSIGNED] = [
                    'completed_at' => $nowIso,
                    'delivery_person_id' => $person->id,
                ];
            }
            $stages[Booking::STATUS_FINALIZED] = [
                'completed_at' => $nowIso,
                'delivery_person_id' => $person->id,
            ];

            $nextStage = in_array($locked->booking_status, [Booking::STATUS_DELIVERY_MAN_ASSIGNED, Booking::STATUS_FINALIZED], true)
                ? Booking::STATUS_DELIVERY_MAN_PICKED_UP
                : $locked->booking_status;

            $locked->forceFill([
                'completed_operational_stages' => $stages,
                'final_amount_minor' => $pricing['final_total_minor'],
                'booking_status' => $nextStage,
                'version' => ((int) $locked->version) + 1,
            ])->save();

            $this->storeEarningsService->getOrCreateSnapshotForBooking($locked, null);

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'collection_finalized_by_dp',
                'summary' => "Collection finalized by Delivery Person {$person->full_name} ({$person->code}).",
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'final_amount_minor' => $pricing['final_total_minor'],
                    'new_status' => $nextStage,
                ],
            ]);

            return $locked;
        });
    }

    public function markCollectionPickedUp(Booking $booking, DeliveryPerson $person): Booking
    {
        return DB::transaction(function () use ($booking, $person) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
                throw new RuntimeException('Cancelled bookings cannot be updated.');
            }

            $completedStages = $locked->getCompletedOperationalStatuses();
            if (! in_array(Booking::STATUS_FINALIZED, $completedStages, true)) {
                throw new RuntimeException('Complete the Finalized stage before marking Delivery Man Picked Up done.');
            }

            if (in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true)) {
                throw new RuntimeException('Delivery Man Picked Up is already marked done.');
            }

            if ($locked->booking_status !== Booking::STATUS_DELIVERY_MAN_PICKED_UP) {
                throw new RuntimeException('Booking is not currently at the Delivery Man Picked Up stage.');
            }

            $now = now();
            $nowIso = $now->toIso8601String();
            $stages = is_array($locked->completed_operational_stages) ? $locked->completed_operational_stages : [];
            $stages[Booking::STATUS_DELIVERY_MAN_PICKED_UP] = [
                'completed_at' => $nowIso,
                'delivery_person_id' => $person->id,
            ];
            $locked->forceFill([
                'completed_operational_stages' => $stages,
                'booking_status' => Booking::STATUS_STORE_RECEIVED,
                'version' => ((int) $locked->version) + 1,
            ])->save();

            $assignment = $locked->currentCollectionAssignment()->lockForUpdate()->first();
            if ($assignment && (int) $assignment->delivery_person_id === (int) $person->id) {
                $assignment->update([
                    'status' => \Modules\Admin\Models\DeliveryAssignment::STATUS_PICKED_UP,
                    'picked_up_at' => $assignment->picked_up_at ?: $now,
                ]);
            }

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'collection_picked_up_by_dp',
                'summary' => "Delivery Man Picked Up marked done by Delivery Person {$person->full_name} ({$person->code}).",
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'completed_stage' => Booking::STATUS_DELIVERY_MAN_PICKED_UP,
                    'new_status' => Booking::STATUS_STORE_RECEIVED,
                ],
            ]);

            return $locked;
        });
    }
}
