<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCustomerPayment;
use App\Models\DeliveryPersonCashTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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

        $isPaymentSatisfied = ((bool) $pricing['is_pricing_complete'] && $outstandingMinor === 0)
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
        throw new RuntimeException('Online payments must be completed by the customer on their own account.');
    }

    public function initiateStripeSession(Booking $booking, DeliveryPerson $person): array
    {
        throw new RuntimeException('Online payments must be completed by the customer on their own account.');
    }

    public function initiateCustomerPayPalOrder(Booking $booking): array
    {
        if ($booking->cancelled_at !== null || $booking->booking_status === Booking::STATUS_CANCELLED) {
            throw new RuntimeException('Cancelled bookings cannot be paid.');
        }

        if ($booking->collection_otp_verified_at === null) {
            throw new RuntimeException('Collection OTP must be verified before initiating PayPal payment.');
        }

        if (strtolower((string) $booking->payment_gateway) !== 'paypal') {
            throw new RuntimeException('PayPal is not available because this booking uses a different payment method.');
        }

        if (! $this->paypalService->isActive()) {
            throw new RuntimeException('PayPal is currently unavailable in payment settings.');
        }

        $state = $this->getPaymentState($booking);
        if (! $state['is_pricing_complete']) {
            throw new RuntimeException('Awaiting Final Amount. Operational pricing must be completed before paying.');
        }
        if ($state['outstanding_minor'] <= 0) {
            throw new RuntimeException('This booking has no outstanding balance.');
        }

        $returnUrl = URL::temporarySignedRoute('booking.paypal.capture', now()->addHours(2), ['booking' => $booking->id]);
        $cancelUrl = route('orders.show', $booking->reference);

        $result = $this->paypalService->createOrder(
            $booking,
            $returnUrl,
            $cancelUrl,
            $state['outstanding_minor'],
            $state['currency']
        );

        if (! empty($result['order_id'])) {
            DB::transaction(function () use ($booking, $state, $result) {
                $booking->forceFill([
                    'paypal_order_id' => $result['order_id'],
                    'customer_payment_channel' => 'paypal',
                    'last_payment_error' => null,
                ])->save();

                BookingCustomerPayment::query()->updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'payment_method' => BookingCustomerPayment::METHOD_PAYPAL,
                        'external_reference' => $result['order_id'],
                    ],
                    [
                        'delivery_person_id' => null,
                        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
                        'amount_minor' => $state['outstanding_minor'],
                        'currency' => $state['currency'],
                        'status' => BookingCustomerPayment::STATUS_PENDING,
                        'idempotency_key' => 'paypal-order:' . $booking->id . ':' . $result['order_id'],
                        'notes' => 'PayPal order initiated by customer',
                    ]
                );
            });
        }

        return $result;
    }

    public function initiateCustomerStripeCheckout(Booking $booking): array
    {
        if ($booking->cancelled_at !== null || $booking->booking_status === Booking::STATUS_CANCELLED) {
            throw new RuntimeException('Cancelled bookings cannot be paid.');
        }

        if ($booking->collection_otp_verified_at === null) {
            throw new RuntimeException('Collection OTP must be verified before initiating Stripe payment.');
        }

        if (strtolower((string) $booking->payment_gateway) !== 'stripe') {
            throw new RuntimeException('Stripe is not available because this booking uses a different payment method.');
        }

        $state = $this->getPaymentState($booking);
        if (! $state['is_pricing_complete']) {
            throw new RuntimeException('Awaiting Final Amount. Operational pricing must be completed before paying.');
        }
        if ($state['outstanding_minor'] <= 0) {
            throw new RuntimeException('This booking has no outstanding balance.');
        }

        if (! $this->stripeService->isActive()) {
            throw new RuntimeException('Stripe is currently unavailable in payment settings.');
        }

        $booking->loadMissing('user');
        $successUrl = URL::temporarySignedRoute('booking.stripe.return', now()->addHours(2), ['booking' => $booking->id])
            . '&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = URL::temporarySignedRoute('booking.stripe.cancel', now()->addHours(2), ['booking' => $booking->id]);

        $idempotencyKey = 'cust-stripe-cs-' . $booking->id . '-' . $state['outstanding_minor'] . '-' . now()->format('YmdHi');
        $session = $this->stripeService->createCheckoutSession(
            booking: $booking,
            amountMinor: $state['outstanding_minor'],
            currency: $state['currency'],
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            idempotencyKey: $idempotencyKey
        );

        DB::transaction(function () use ($booking, $state, $session) {
            $booking->forceFill([
                'stripe_payment_intent_id' => $session['payment_intent_id'] ?: $booking->stripe_payment_intent_id,
                'customer_payment_channel' => 'stripe',
                'last_payment_error' => null,
            ])->save();

            BookingCustomerPayment::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
                    'external_reference' => $session['session_id'],
                ],
                [
                    'delivery_person_id' => null,
                    'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
                    'amount_minor' => $state['outstanding_minor'],
                    'currency' => $state['currency'],
                    'status' => BookingCustomerPayment::STATUS_PENDING,
                    'idempotency_key' => 'stripe-cs:' . $booking->id . ':' . $session['session_id'],
                    'notes' => 'Stripe Checkout Session initiated by customer',
                ]
            );
        });

        return $session;
    }

    /**
     * Create or safely reuse a Stripe PaymentIntent for on-page customer payment (no Checkout redirect).
     *
     * @return array{publishable_key: string, client_secret: string, payment_intent_id: string, amount_minor: int, currency: string}
     */
    public function initiateCustomerStripePaymentIntent(Booking $booking): array
    {
        if ($booking->cancelled_at !== null || $booking->booking_status === Booking::STATUS_CANCELLED) {
            throw new RuntimeException('Cancelled bookings cannot be paid.');
        }

        if ($booking->collection_otp_verified_at === null) {
            throw new RuntimeException('Online payment will be available after OTP verification.');
        }

        if (strtolower((string) $booking->payment_gateway) !== 'stripe') {
            throw new RuntimeException('Stripe is not available because this booking uses a different payment method.');
        }

        $state = $this->getPaymentState($booking);
        if (! $state['is_pricing_complete']) {
            throw new RuntimeException('Awaiting Final Amount.');
        }
        if ($state['is_payment_satisfied'] || $state['outstanding_minor'] <= 0) {
            throw new RuntimeException('Payment Completed.');
        }

        if (! $this->stripeService->isActive()) {
            throw new RuntimeException('Stripe is currently unavailable in payment settings.');
        }

        $publishableKey = (string) ($this->stripeService->publishableKey() ?? '');
        if ($publishableKey === '') {
            throw new RuntimeException('Stripe publishable key is not configured.');
        }

        $amountMinor = (int) $state['outstanding_minor'];
        $currency = strtoupper((string) $state['currency']);
        $intentData = null;

        // Safely reuse existing pending PaymentIntent if amount, currency, and status still match
        if (! empty($booking->stripe_payment_intent_id)) {
            try {
                $existing = $this->stripeService->retrievePaymentIntent((string) $booking->stripe_payment_intent_id);
                $existingStatus = (string) ($existing->status ?? '');
                $existingAmount = (int) ($existing->amount ?? 0);
                $existingCurrency = strtoupper((string) ($existing->currency ?? ''));

                if (in_array($existingStatus, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)
                    && $existingAmount === $amountMinor
                    && $existingCurrency === $currency
                    && ! empty($existing->client_secret)) {
                    $intentData = [
                        'id' => (string) $existing->id,
                        'client_secret' => (string) $existing->client_secret,
                        'status' => $existingStatus,
                        'amount_minor' => $existingAmount,
                        'currency' => $existingCurrency,
                    ];
                }
            } catch (\Throwable) {
                // Fall through to creating a fresh PaymentIntent
            }
        }

        if (! $intentData) {
            $idempotencyKey = 'cust-pi-' . $booking->id . '-' . $amountMinor . '-' . now()->format('YmdHi');
            $intentData = $this->stripeService->createPaymentIntent(
                amountMinor: $amountMinor,
                currency: $currency,
                booking: $booking,
                idempotencyKey: $idempotencyKey
            );
        }

        DB::transaction(function () use ($booking, $amountMinor, $currency, $intentData) {
            $booking->forceFill([
                'stripe_payment_intent_id' => $intentData['id'],
                'customer_payment_channel' => 'stripe',
                'last_payment_error' => null,
            ])->save();

            BookingCustomerPayment::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
                    'external_reference' => $intentData['id'],
                ],
                [
                    'delivery_person_id' => null,
                    'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'status' => BookingCustomerPayment::STATUS_PENDING,
                    'idempotency_key' => 'stripe-pi:' . $booking->id . ':' . $intentData['id'],
                    'notes' => 'Stripe PaymentIntent initiated on-page by customer',
                ]
            );
        });

        return [
            'publishable_key' => $publishableKey,
            'client_secret' => $intentData['client_secret'],
            'payment_intent_id' => $intentData['id'],
            'amount_minor' => $amountMinor,
            'currency' => $currency,
        ];
    }

    public function recordVerifiedGatewayPayment(
        Booking $booking,
        string $method,
        string $externalReference,
        int $amountMinor,
        string $currency,
        string $notes = '',
        ?string $webhookEventId = null
    ): BookingCustomerPayment {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Verified payment amount must be greater than zero.');
        }

        $payment = DB::transaction(function () use ($booking, $method, $externalReference, $amountMinor, $currency, $notes, $webhookEventId) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $bookingCurrency = strtoupper((string) ($locked->currency ?: $this->currencyService->code()));
            if (strtoupper($currency) !== $bookingCurrency) {
                throw new RuntimeException("Payment currency {$currency} does not match booking currency {$bookingCurrency}.");
            }

            $alreadyConfirmed = BookingCustomerPayment::query()
                ->where('booking_id', $locked->id)
                ->where('payment_method', $method)
                ->where('external_reference', $externalReference)
                ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
                ->first();

            if ($alreadyConfirmed) {
                return $alreadyConfirmed;
            }

            $now = now();
            $pending = BookingCustomerPayment::query()
                ->where('booking_id', $locked->id)
                ->where('payment_method', $method)
                ->where('external_reference', $externalReference)
                ->first();

            if ($pending) {
                $pending->forceFill([
                    'amount_minor' => $amountMinor,
                    'currency' => $bookingCurrency,
                    'status' => BookingCustomerPayment::STATUS_CONFIRMED,
                    'collected_at' => $pending->collected_at ?? $now,
                    'verified_at' => $now,
                    'notes' => trim(($pending->notes ? $pending->notes . ' | ' : '') . $notes),
                ])->save();
                $payment = $pending;
            } else {
                $payment = BookingCustomerPayment::query()->create([
                    'booking_id' => $locked->id,
                    'payment_method' => $method,
                    'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
                    'amount_minor' => $amountMinor,
                    'currency' => $bookingCurrency,
                    'status' => BookingCustomerPayment::STATUS_CONFIRMED,
                    'external_reference' => $externalReference,
                    'idempotency_key' => $method . '-confirmed:' . $locked->id . ':' . $externalReference,
                    'notes' => $notes ?: null,
                    'collected_at' => $now,
                    'verified_at' => $now,
                ]);
            }

            $state = $this->getPaymentState($locked);
            $newStatus = $state['outstanding_minor'] === 0 ? 'paid' : 'partially_paid';

            $locked->forceFill([
                'customer_payment_channel' => $method,
                'payment_status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? ($locked->paid_at ?: $now) : $locked->paid_at,
                'last_payment_error' => null,
            ])->save();

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'system',
                'actor_id' => null,
                'actor_name' => strtoupper($method) . ' Gateway',
                'action' => 'customer_gateway_payment_confirmed',
                'summary' => sprintf(
                    'Verified %s payment %s (%s) for booking %s.',
                    strtoupper($method),
                    $this->currencyService->formatMinor($amountMinor, $bookingCurrency),
                    $externalReference,
                    $locked->reference
                ),
                'changes' => [
                    'payment_id' => $payment->id,
                    'payment_method' => $method,
                    'external_reference' => $externalReference,
                    'amount_minor' => $amountMinor,
                    'currency' => $bookingCurrency,
                    'outstanding_minor' => $state['outstanding_minor'],
                    'new_payment_status' => $newStatus,
                    'webhook_event_id' => $webhookEventId,
                ],
            ]);

            $this->autoFinalizeAfterVerifiedPayment($locked, $state);

            return $payment;
        });

        if ($method === BookingCustomerPayment::METHOD_STRIPE) {
            try {
                app(StripeFeeReconciliationService::class)->reconcilePayment($payment);
            } catch (\Throwable) {
                // Non-blocking: deferred to manual or scheduled reconciliation
            }
        }

        return $payment;
    }

    /**
     * Automatically advance booking through the Finalized stage once verified online payment
     * satisfies the balance and all operational prerequisites are met (idempotent).
     *
     * @param  array<string, mixed>  $state
     */
    protected function autoFinalizeAfterVerifiedPayment(Booking $locked, array $state): void
    {
        if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
            return;
        }

        if ($locked->collection_otp_verified_at === null) {
            return;
        }

        if (empty($state['is_pricing_complete']) || empty($state['is_payment_satisfied'])) {
            return;
        }

        $stages = is_array($locked->completed_operational_stages) ? $locked->completed_operational_stages : [];
        if (isset($stages[Booking::STATUS_FINALIZED])) {
            return;
        }

        if (! in_array($locked->booking_status, [Booking::STATUS_DELIVERY_MAN_ASSIGNED, Booking::STATUS_FINALIZED], true)) {
            return;
        }

        $nowIso = now()->toIso8601String();
        if ($locked->booking_status === Booking::STATUS_DELIVERY_MAN_ASSIGNED && ! isset($stages[Booking::STATUS_DELIVERY_MAN_ASSIGNED])) {
            $stages[Booking::STATUS_DELIVERY_MAN_ASSIGNED] = [
                'completed_at' => $nowIso,
                'auto_completed' => true,
            ];
        }

        $stages[Booking::STATUS_FINALIZED] = [
            'completed_at' => $nowIso,
            'auto_finalized_after_payment' => true,
        ];

        $finalTotalMinor = (int) ($state['pricing']['final_total_minor'] ?? $state['total_minor']);

        $locked->forceFill([
            'completed_operational_stages' => $stages,
            'final_amount_minor' => $finalTotalMinor,
            'booking_status' => Booking::STATUS_DELIVERY_MAN_PICKED_UP,
            'version' => ((int) $locked->version) + 1,
        ])->save();

        $this->storeEarningsService->getOrCreateSnapshotForBooking($locked, null);

        BookingAuditLog::create([
            'booking_id' => $locked->id,
            'actor_type' => 'system',
            'actor_id' => null,
            'actor_name' => 'Payment Auto-Finalizer',
            'action' => 'collection_auto_finalized_after_online_payment',
            'summary' => sprintf(
                'Booking %s automatically finalized after verified online payment (%s).',
                $locked->reference,
                $this->currencyService->formatMinor($finalTotalMinor, $state['currency'])
            ),
            'changes' => [
                'final_amount_minor' => $finalTotalMinor,
                'new_status' => Booking::STATUS_DELIVERY_MAN_PICKED_UP,
            ],
        ]);
    }

    public function markGatewayPaymentFailed(Booking $booking, string $method, ?string $externalReference, string $reason): void
    {
        DB::transaction(function () use ($booking, $method, $externalReference, $reason) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($externalReference) {
                BookingCustomerPayment::query()
                    ->where('booking_id', $locked->id)
                    ->where('payment_method', $method)
                    ->where('external_reference', $externalReference)
                    ->where('status', BookingCustomerPayment::STATUS_PENDING)
                    ->update([
                        'status' => BookingCustomerPayment::STATUS_FAILED,
                        'notes' => $reason,
                    ]);
            }

            if (! in_array((string) $locked->payment_status, ['paid', 'partially_paid'], true)) {
                $locked->forceFill([
                    'payment_status' => 'failed',
                    'last_payment_error' => $reason,
                ])->save();
            } else {
                $locked->forceFill([
                    'last_payment_error' => $reason,
                ])->save();
            }
        });
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
