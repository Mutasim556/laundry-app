<?php

namespace App\Services;

use App\Models\BookingCustomerPayment;
use Illuminate\Support\Facades\Log;

class StripeFeeReconciliationService
{
    public function __construct(
        protected StripePaymentService $stripeService
    ) {}

    public function reconcilePayment(BookingCustomerPayment $payment, bool $force = false): BookingCustomerPayment
    {
        if ($payment->payment_method !== BookingCustomerPayment::METHOD_STRIPE) {
            return $payment;
        }

        if (! in_array($payment->status, [BookingCustomerPayment::STATUS_CONFIRMED, BookingCustomerPayment::STATUS_REFUNDED], true)) {
            return $payment;
        }

        if (
            ! $force
            && $payment->fee_reconciliation_status === 'reconciled'
            && ! empty($payment->stripe_balance_transaction_id)
        ) {
            return $payment;
        }

        $ref = trim((string) (
            $payment->stripe_payment_intent_id
            ?: $payment->external_reference
            ?: $payment->booking?->stripe_payment_intent_id
            ?: ''
        ));

        if ($ref === '') {
            return $payment;
        }

        try {
            if (! $this->stripeService->isConfigured()) {
                return $payment;
            }

            $bt = $this->stripeService->retrieveBalanceTransactionForPaymentReference($ref);
            if (! $bt) {
                $payment->forceFill([
                    'stripe_payment_intent_id' => str_starts_with($ref, 'pi_') ? $ref : $payment->stripe_payment_intent_id,
                    'fee_reconciliation_status' => 'pending',
                ])->save();

                return $payment;
            }

            $payment->forceFill([
                'stripe_payment_intent_id' => $bt['payment_intent_id'],
                'stripe_charge_id' => $bt['charge_id'],
                'stripe_balance_transaction_id' => $bt['balance_transaction_id'],
                'gateway_gross_minor' => $bt['amount_minor'],
                'gateway_fee_minor' => $bt['fee_minor'],
                'gateway_net_minor' => $bt['net_minor'],
                'gateway_fee_currency' => $bt['currency'],
                'gateway_fee_details' => $bt['fee_details'],
                'fee_reconciliation_status' => 'reconciled',
                'fee_reconciled_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Stripe fee reconciliation deferred', [
                'payment_id' => $payment->id,
                'reference' => $ref,
                'error' => $e->getMessage(),
            ]);

            if (! $payment->fee_reconciliation_status) {
                $payment->forceFill(['fee_reconciliation_status' => 'pending'])->save();
            }
        }

        return $payment->fresh();
    }

    /**
     * Reconcile pending or unreconciled confirmed Stripe payments idempotently.
     *
     * @return array{checked: int, reconciled: int, pending: int}
     */
    public function reconcilePendingPayments(?string $currency = null, int $limit = 30): array
    {
        $query = BookingCustomerPayment::query()
            ->with('booking')
            ->where('payment_method', BookingCustomerPayment::METHOD_STRIPE)
            ->whereIn('status', [BookingCustomerPayment::STATUS_CONFIRMED, BookingCustomerPayment::STATUS_REFUNDED])
            ->where(function ($q) {
                $q->whereNull('fee_reconciliation_status')
                    ->orWhere('fee_reconciliation_status', '!=', 'reconciled')
                    ->orWhereNull('stripe_balance_transaction_id');
            })
            ->orderByDesc('id')
            ->limit($limit);

        if ($currency) {
            $query->where('currency', strtoupper(trim($currency)));
        }

        $payments = $query->get();
        $reconciled = 0;
        $pending = 0;

        foreach ($payments as $p) {
            $updated = $this->reconcilePayment($p, false);
            if ($updated->fee_reconciliation_status === 'reconciled') {
                $reconciled++;
            } else {
                $pending++;
            }
        }

        return [
            'checked' => $payments->count(),
            'reconciled' => $reconciled,
            'pending' => $pending,
        ];
    }
}
