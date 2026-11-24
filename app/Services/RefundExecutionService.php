<?php

namespace App\Services;

use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCustomerPayment;
use App\Models\ClaimStatusHistory;
use App\Models\CustomerServiceClaim;
use App\Models\RefundTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundExecutionService
{
    public function __construct(
        protected RefundCalculationService $calculationService,
        protected StripePaymentService $stripeService,
        protected PayPalPaymentService $paypalService,
        protected CurrencyService $currencyService
    ) {}

    public function approveRefund(
        Booking $booking,
        ?CustomerServiceClaim $claim,
        int $amountMinor,
        string $method,
        int $adminId,
        ?BookingCustomerPayment $sourcePayment = null,
        ?string $notes = null
    ): RefundTransaction {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages(['amount' => ['Refund amount must be greater than zero.']]);
        }

        return DB::transaction(function () use ($booking, $claim, $amountMinor, $method, $adminId, $sourcePayment, $notes) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $preview = $this->calculationService->buildPreview($lockedBooking, $claim);
            if ($amountMinor > $preview['remaining_refundable_minor']) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Requested refund exceeds remaining refundable amount (' .
                        $this->currencyService->formatMinor($preview['remaining_refundable_minor'], $preview['currency']) . ').',
                    ],
                ]);
            }

            $now = now();
            $initialStatus = $method === RefundTransaction::METHOD_COD_MANUAL
                ? RefundTransaction::STATUS_AWAITING_PAYOUT
                : RefundTransaction::STATUS_APPROVED;

            $refund = RefundTransaction::create([
                'refund_reference' => 'RFD-' . $now->format('Y') . '-' . strtoupper(Str::random(6)),
                'claim_id' => $claim?->id,
                'booking_id' => $lockedBooking->id,
                'booking_customer_payment_id' => $sourcePayment?->id,
                'payment_method' => $method,
                'amount_minor' => $amountMinor,
                'currency' => $preview['currency'],
                'status' => $initialStatus,
                'idempotency_key' => 'rfd:' . $lockedBooking->id . ':' . ($claim?->id ?? 0) . ':' . Str::uuid(),
                'approved_by_admin_id' => $adminId,
                'notes' => $notes ? trim($notes) : null,
            ]);

            $refund->refund_reference = 'RFD-' . $now->format('Y') . '-' . str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT);
            $refund->save();

            if ($claim) {
                $oldStatus = $claim->status;
                $claim->approved_amount_minor = ((int) $claim->approved_amount_minor) + $amountMinor;
                if ($claim->canTransitionTo(CustomerServiceClaim::STATUS_AWAITING_REFUND_PROCESSING)) {
                    $claim->status = CustomerServiceClaim::STATUS_AWAITING_REFUND_PROCESSING;
                }
                $claim->reviewed_by_admin_id = $adminId;
                $claim->reviewed_at = $now;
                $claim->save();

                ClaimStatusHistory::create([
                    'claim_id' => $claim->id,
                    'from_status' => $oldStatus,
                    'to_status' => $claim->status,
                    'actor_type' => 'admin',
                    'actor_id' => $adminId,
                    'actor_name' => 'Admin #' . $adminId,
                    'note' => "Approved refund {$refund->refund_reference} (" . $this->currencyService->formatMinor($amountMinor, $preview['currency']) . ").",
                ]);
            }

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'action' => 'refund_approved',
                'summary' => "Approved {$method} refund {$refund->refund_reference} (" . $this->currencyService->formatMinor($amountMinor, $preview['currency']) . ")",
                'changes' => [
                    'refund_id' => $refund->id,
                    'refund_reference' => $refund->refund_reference,
                    'amount_minor' => $amountMinor,
                    'method' => $method,
                ],
            ]);

            return $refund;
        });
    }

    public function executeGatewayRefund(RefundTransaction $refund, int $adminId): RefundTransaction
    {
        if ($refund->isCompletedDisbursement()) {
            return $refund;
        }

        if (! in_array($refund->payment_method, [RefundTransaction::METHOD_STRIPE, RefundTransaction::METHOD_PAYPAL], true)) {
            throw ValidationException::withMessages(['payment_method' => ['Only Stripe and PayPal refunds can be executed via gateway API.']]);
        }

        $refund->loadMissing(['booking', 'customerPayment', 'claim']);
        $sourcePayment = $refund->customerPayment
            ?? $refund->booking->customerPayments()
                ->where('payment_method', $refund->payment_method)
                ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
                ->latest('id')
                ->first();

        if (! $sourcePayment) {
            throw ValidationException::withMessages(['booking_customer_payment_id' => ['No confirmed source payment found for this gateway refund.']]);
        }

        $refund->update([
            'status' => RefundTransaction::STATUS_PROCESSING,
            'processed_by_admin_id' => $adminId,
        ]);

        try {
            if ($refund->payment_method === RefundTransaction::METHOD_STRIPE) {
                $piOrCharge = $sourcePayment->stripe_payment_intent_id
                    ?: $sourcePayment->stripe_charge_id
                    ?: $sourcePayment->external_reference
                    ?: $refund->booking->stripe_payment_intent_id;

                if (! $piOrCharge) {
                    throw new \RuntimeException('Stripe PaymentIntent/Charge reference is missing.');
                }

                $res = $this->stripeService->createRefund(
                    $piOrCharge,
                    (int) $refund->amount_minor,
                    $refund->idempotency_key
                );

                $isDone = strtolower($res['status']) === 'succeeded';
                $refund->update([
                    'provider_refund_id' => $res['id'],
                    'status' => $isDone ? RefundTransaction::STATUS_SUCCEEDED : RefundTransaction::STATUS_PROCESSING,
                    'processed_at' => now(),
                    'reconciled_at' => $isDone ? now() : null,
                ]);
            } else {
                $captureId = $sourcePayment->external_reference ?: $refund->booking->paypal_capture_id;
                if (! $captureId) {
                    throw new \RuntimeException('PayPal Capture ID is missing.');
                }

                $res = $this->paypalService->refundCapture(
                    $captureId,
                    (int) $refund->amount_minor,
                    $refund->currency,
                    $refund->idempotency_key,
                    $refund->notes
                );

                $isDone = strtoupper($res['status']) === 'COMPLETED';
                $refund->update([
                    'provider_refund_id' => $res['id'],
                    'status' => $isDone ? RefundTransaction::STATUS_SUCCEEDED : RefundTransaction::STATUS_PROCESSING,
                    'processed_at' => now(),
                    'reconciled_at' => $isDone ? now() : null,
                ]);
            }
        } catch (\Throwable $e) {
            $refund->update([
                'status' => RefundTransaction::STATUS_FAILED,
                'failure_reason' => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw ValidationException::withMessages(['refund' => ['Gateway refund failed: ' . $e->getMessage()]]);
        }

        if ($refund->isCompletedDisbursement()) {
            $this->finalizeCompletedRefund($refund, $sourcePayment, $adminId);
        }

        return $refund->fresh();
    }

    public function markCodRefundPaid(RefundTransaction $refund, array $data, int $adminId): RefundTransaction
    {
        if ($refund->isCompletedDisbursement()) {
            return $refund;
        }

        $refund->loadMissing(['booking', 'customerPayment', 'claim']);

        DB::transaction(function () use ($refund, $data, $adminId) {
            $refund->update([
                'status' => RefundTransaction::STATUS_PAID,
                'payout_recipient' => trim((string) $data['payout_recipient']),
                'payout_method' => trim((string) $data['payout_method']),
                'external_reference' => ! empty($data['external_reference']) ? trim((string) $data['external_reference']) : null,
                'processed_by_admin_id' => $adminId,
                'processed_at' => now(),
                'reconciled_at' => now(),
            ]);

            $this->finalizeCompletedRefund($refund, $refund->customerPayment, $adminId);
        });

        return $refund->fresh();
    }

    protected function finalizeCompletedRefund(RefundTransaction $refund, ?BookingCustomerPayment $sourcePayment, int $adminId): void
    {
        $booking = $refund->booking;
        $totalRefunded = (int) RefundTransaction::query()
            ->where('booking_id', $booking->id)
            ->whereIn('status', [RefundTransaction::STATUS_SUCCEEDED, RefundTransaction::STATUS_PAID, RefundTransaction::STATUS_RECONCILED])
            ->sum('amount_minor');

        $confirmedPaid = (int) $booking->customerPayments()
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->sum('amount_minor');

        $booking->forceFill([
            'refunded_amount_minor' => $totalRefunded,
            'refund_status' => $totalRefunded >= $confirmedPaid && $confirmedPaid > 0 ? 'succeeded' : 'partial',
            'payment_status' => $totalRefunded >= $confirmedPaid && $confirmedPaid > 0 ? 'refunded' : 'partial_refund',
            'stripe_refund_id' => $refund->payment_method === RefundTransaction::METHOD_STRIPE ? ($refund->provider_refund_id ?: $booking->stripe_refund_id) : $booking->stripe_refund_id,
        ])->save();

        if ($sourcePayment && $totalRefunded >= (int) $sourcePayment->amount_minor) {
            $sourcePayment->update(['status' => BookingCustomerPayment::STATUS_REFUNDED]);
        }

        if ($refund->claim) {
            $oldStatus = $refund->claim->status;
            if ($refund->claim->canTransitionTo(CustomerServiceClaim::STATUS_REFUNDED)) {
                $refund->claim->update(['status' => CustomerServiceClaim::STATUS_REFUNDED]);
                ClaimStatusHistory::create([
                    'claim_id' => $refund->claim->id,
                    'from_status' => $oldStatus,
                    'to_status' => CustomerServiceClaim::STATUS_REFUNDED,
                    'actor_type' => 'admin',
                    'actor_id' => $adminId,
                    'actor_name' => 'Admin #' . $adminId,
                    'note' => "Refund {$refund->refund_reference} completed.",
                ]);
            }
        }

        BookingAuditLog::create([
            'booking_id' => $booking->id,
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'actor_name' => 'Admin #' . $adminId,
            'action' => 'refund_completed',
            'summary' => "Completed refund {$refund->refund_reference} (" . $this->currencyService->formatMinor((int) $refund->amount_minor, $refund->currency) . ")",
            'changes' => [
                'refund_id' => $refund->id,
                'provider_refund_id' => $refund->provider_refund_id,
                'total_refunded_minor' => $totalRefunded,
            ],
        ]);

        try {
            if (! empty($booking->customer_email)) {
                Mail::to($booking->customer_email)->send(new BookingNotificationMail(
                    $booking,
                    "Refund Completed ({$refund->refund_reference})",
                    "Your refund of " . $this->currencyService->formatMinor((int) $refund->amount_minor, $refund->currency) . " for booking {$booking->reference} has been processed."
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Refund completion email skipped', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }
}
