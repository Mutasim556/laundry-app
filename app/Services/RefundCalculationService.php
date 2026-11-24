<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\CustomerServiceClaim;
use App\Models\RefundTransaction;

class RefundCalculationService
{
    public function __construct(
        protected PolicyResolutionService $policyService,
        protected CurrencyService $currencyService
    ) {}

    /**
     * Build a decimal-safe, server-authoritative refund calculation preview for Admin review.
     *
     * @return array<string, mixed>
     */
    public function buildPreview(Booking $booking, ?CustomerServiceClaim $claim = null): array
    {
        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
        $rules = $this->policyService->getEffectiveRules($booking);

        $confirmedPayments = $booking->customerPayments()
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->orderBy('id')
            ->get();

        $confirmedPaidMinor = (int) $confirmedPayments->sum('amount_minor');

        $alreadyRefundedMinor = (int) RefundTransaction::query()
            ->where('booking_id', $booking->id)
            ->whereIn('status', [
                RefundTransaction::STATUS_SUCCEEDED,
                RefundTransaction::STATUS_PAID,
                RefundTransaction::STATUS_RECONCILED,
            ])
            ->sum('amount_minor');

        $inFlightRefundMinor = (int) RefundTransaction::query()
            ->where('booking_id', $booking->id)
            ->whereIn('status', [
                RefundTransaction::STATUS_APPROVED,
                RefundTransaction::STATUS_AWAITING_PAYOUT,
                RefundTransaction::STATUS_PROCESSING,
            ])
            ->sum('amount_minor');

        $remainingRefundableMinor = max(0, $confirmedPaidMinor - $alreadyRefundedMinor - $inFlightRefundMinor);

        // Policy percentage by stage (used as guidance for cancellation/refund)
        $stagePercentages = (array) ($rules['stage_refund_percentages'] ?? []);
        $stagePct = (int) ($stagePercentages[$booking->booking_status] ?? 100);
        $fixedDeductionMinor = max(0, (int) ($rules['fixed_deduction_minor'] ?? 0));

        $policySuggestedMinor = (int) round($confirmedPaidMinor * ($stagePct / 100));
        if ($fixedDeductionMinor > 0 && $policySuggestedMinor > $fixedDeductionMinor) {
            $policySuggestedMinor -= $fixedDeductionMinor;
        }
        $policySuggestedMinor = min($remainingRefundableMinor, max(0, $policySuggestedMinor));

        $itemLevelSuggestedMinor = 0;
        if ($claim) {
            $claim->loadMissing('items.operationalItem');
            foreach ($claim->items as $ci) {
                if ($ci->claimed_amount_minor > 0) {
                    $itemLevelSuggestedMinor += (int) $ci->claimed_amount_minor;
                } elseif ($ci->operationalItem) {
                    $itemLevelSuggestedMinor += (int) $ci->operationalItem->computeLineTotalMinor();
                }
            }
        }

        return [
            'currency' => $currency,
            'confirmed_paid_minor' => $confirmedPaidMinor,
            'already_refunded_minor' => $alreadyRefundedMinor,
            'in_flight_refund_minor' => $inFlightRefundMinor,
            'remaining_refundable_minor' => $remainingRefundableMinor,
            'stage_refund_percentage' => $stagePct,
            'fixed_deduction_minor' => $fixedDeductionMinor,
            'policy_suggested_minor' => $policySuggestedMinor,
            'item_level_suggested_minor' => min($remainingRefundableMinor, $itemLevelSuggestedMinor),
            'confirmed_payments' => $confirmedPayments,
        ];
    }
}
