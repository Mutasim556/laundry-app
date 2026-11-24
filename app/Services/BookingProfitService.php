<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingFinancialSettlement;
use App\Models\BookingStoreEarning;

class BookingProfitService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected BookingFinancialSettlementService $settlementService
    ) {}

    /**
     * Calculate read-only booking contribution profit and margin from authoritative
     * persisted snapshots (never mutates any account balance or ledger).
     *
     * @return array<string, mixed>
     */
    public function calculateForBooking(Booking $booking): array
    {
        $booking->loadMissing([
            'currentStoreAssignment.store',
            'currentStoreEarning',
            'currentCollectionAssignment',
            'currentFinalDeliveryAssignment',
            'currentFinancialSettlement',
        ]);

        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService, false);
        $allocationPreview = $this->settlementService->buildPreview($booking);

        $completedStages = $booking->getCompletedOperationalStatuses();
        $isFinalizedDone = in_array(Booking::STATUS_FINALIZED, $completedStages, true)
            && (bool) $pricingSummary['is_pricing_complete'];
        $isCompletedDone = $booking->booking_status === Booking::STATUS_COMPLETED
            && in_array(Booking::STATUS_COMPLETED, $completedStages, true);

        /** @var BookingStoreEarning|null $storeEarning */
        $storeEarning = $booking->currentStoreEarning;
        /** @var BookingFinancialSettlement|null $settlement */
        $settlement = $booking->currentFinancialSettlement;

        // 1. Eligible Service Revenue & Store Net Earnings (use persisted snapshot when available)
        if ($storeEarning) {
            $eligibleServiceMinor = (int) $storeEarning->eligible_service_amount_minor;
            $storeNetEarningsMinor = (int) $storeEarning->net_store_earnings_minor;
            $storeCommissionRate = (float) $storeEarning->commission_rate;
            $hasStoreBasis = true;
        } else {
            $eligibleServiceMinor = (int) $pricingSummary['items_subtotal_minor'];
            $store = $booking->currentStoreAssignment?->store;
            if ($store && $store->commission_rate !== null) {
                $storeCommissionRate = max(0.0, min(100.0, (float) $store->commission_rate));
                $commissionMinor = (int) round($eligibleServiceMinor * ($storeCommissionRate / 100));
                $storeNetEarningsMinor = max(0, $eligibleServiceMinor - $commissionMinor);
                $hasStoreBasis = true;
            } else {
                $storeCommissionRate = null;
                $storeNetEarningsMinor = 0;
                $hasStoreBasis = false;
            }
        }

        $storeContributionMinor = $eligibleServiceMinor - $storeNetEarningsMinor;

        // 2. Delivery Revenue & Payouts (use persisted settlement when settled)
        if ($settlement && $settlement->settlement_status === 'settled') {
            $deliveryFeeMinor = (int) $settlement->delivery_fee_minor;
            $collectionPaymentMinor = (int) $settlement->collection_delivery_payment_minor;
            $finalPaymentMinor = (int) $settlement->final_delivery_payment_minor;
            $customerTipMinor = (int) $settlement->customer_tip_minor;
            $collectionTipShareMinor = (int) $settlement->collection_tip_share_minor;
            $finalTipShareMinor = (int) $settlement->final_tip_share_minor;
        } else {
            $deliveryFeeMinor = (int) $allocationPreview['delivery_fee_minor'];
            $collectionPaymentMinor = (int) $allocationPreview['collection_payment_minor'];
            $finalPaymentMinor = (int) $allocationPreview['final_payment_minor'];
            $customerTipMinor = (int) $allocationPreview['customer_tip_minor'];
            $collectionTipShareMinor = (int) $allocationPreview['collection_tip_share_minor'];
            $finalTipShareMinor = (int) $allocationPreview['final_tip_share_minor'];
        }

        $deliveryContributionMinor = $deliveryFeeMinor - $collectionPaymentMinor - $finalPaymentMinor;

        // 3. Tips (pass-through: 50/50 to delivery persons -> 0 profit contribution)
        $allocatedTipsMinor = $collectionTipShareMinor + $finalTipShareMinor;
        $hasTipReconciliationIssue = $allocatedTipsMinor !== $customerTipMinor;

        // 4. Platform Service Fee, Discounts, Refunds & Authority Taxes
        $serviceFeeMinor = max(0, (int) $pricingSummary['service_fee_minor']);
        $discountMinor = max(0, (int) $pricingSummary['discount_minor']);
        $refundedMinor = max(0, (int) ($booking->refunded_amount_minor ?? 0));
        $taxMinor = max(0, (int) $pricingSummary['tax_minor']);
        $otherPlatformCostsMinor = 0; // No recorded gateway fee column exists on bookings

        $otherContributionMinor = $serviceFeeMinor - $discountMinor - $refundedMinor - $otherPlatformCostsMinor;

        // 5. Total Booking Contribution Profit & Recognized Net Revenue
        $totalProfitMinor = $storeContributionMinor + $deliveryContributionMinor + $otherContributionMinor;
        $recognizedNetRevenueMinor = $eligibleServiceMinor + $serviceFeeMinor + $deliveryFeeMinor - $discountMinor - $refundedMinor;

        $marginPercentage = $recognizedNetRevenueMinor > 0
            ? round(($totalProfitMinor / $recognizedNetRevenueMinor) * 100, 2)
            : null;
        $marginPercentageFormatted = $marginPercentage !== null
            ? number_format($marginPercentage, 2, '.', '') . '%'
            : 'N/A';

        // 6. Lifecycle Visibility State
        $isPosted = $isCompletedDone && $settlement !== null && $settlement->settlement_status === 'settled';
        $hasAuthoritativeProfit = $isFinalizedDone && $hasStoreBasis;
        $hasProvisionalEstimate = ! $hasAuthoritativeProfit && $hasStoreBasis && ($eligibleServiceMinor > 0 || $serviceFeeMinor > 0 || $deliveryFeeMinor > 0);

        if ($booking->cancelled_at !== null) {
            $statusLabel = 'Booking Cancelled';
            $statusBadge = 'secondary';
        } elseif ($isPosted) {
            $statusLabel = 'Earned / Posted Booking Profit';
            $statusBadge = 'success';
        } elseif ($hasAuthoritativeProfit) {
            $statusLabel = 'Finalized Booking Profit';
            $statusBadge = 'primary';
        } elseif ($hasProvisionalEstimate) {
            $statusLabel = 'Estimated Booking Profit (Provisional)';
            $statusBadge = 'warning';
        } else {
            $statusLabel = 'Profit Not Finalized';
            $statusBadge = 'warning';
        }

        return [
            'currency' => $currency,
            'status_label' => $statusLabel,
            'status_badge' => $statusBadge,
            'is_finalized' => $hasAuthoritativeProfit,
            'is_posted' => $isPosted,
            'is_provisional' => ! $hasAuthoritativeProfit,
            'has_provisional_estimate' => $hasProvisionalEstimate,
            'has_store_basis' => $hasStoreBasis,
            'store_commission_rate' => $storeCommissionRate,
            'eligible_service_revenue_minor' => $eligibleServiceMinor,
            'store_net_earnings_minor' => $storeNetEarningsMinor,
            'store_contribution_minor' => $storeContributionMinor,
            'delivery_fee_minor' => $deliveryFeeMinor,
            'collection_payment_minor' => $collectionPaymentMinor,
            'final_payment_minor' => $finalPaymentMinor,
            'delivery_contribution_minor' => $deliveryContributionMinor,
            'service_fee_minor' => $serviceFeeMinor,
            'discount_minor' => $discountMinor,
            'refunded_minor' => $refundedMinor,
            'tax_minor' => $taxMinor,
            'other_platform_costs_minor' => $otherPlatformCostsMinor,
            'other_contribution_minor' => $otherContributionMinor,
            'customer_tip_minor' => $customerTipMinor,
            'allocated_tips_minor' => $allocatedTipsMinor,
            'has_tip_reconciliation_issue' => $hasTipReconciliationIssue,
            'recognized_net_revenue_minor' => $recognizedNetRevenueMinor,
            'total_profit_minor' => $totalProfitMinor,
            'margin_percentage' => $marginPercentage,
            'margin_percentage_formatted' => $marginPercentageFormatted,
            'formatted' => [
                'eligible_service_revenue' => $this->currencyService->formatMinor($eligibleServiceMinor, $currency),
                'store_net_earnings' => $this->currencyService->formatMinor($storeNetEarningsMinor, $currency),
                'store_contribution' => $this->formatSignedMinor($storeContributionMinor, $currency),
                'delivery_fee' => $this->currencyService->formatMinor($deliveryFeeMinor, $currency),
                'collection_payment' => $this->currencyService->formatMinor($collectionPaymentMinor, $currency),
                'final_payment' => $this->currencyService->formatMinor($finalPaymentMinor, $currency),
                'delivery_contribution' => $this->formatSignedMinor($deliveryContributionMinor, $currency),
                'service_fee' => $this->currencyService->formatMinor($serviceFeeMinor, $currency),
                'discount' => $this->currencyService->formatMinor($discountMinor, $currency),
                'refunded' => $this->currencyService->formatMinor($refundedMinor, $currency),
                'tax' => $this->currencyService->formatMinor($taxMinor, $currency),
                'other_contribution' => $this->formatSignedMinor($otherPlatformCostsMinor === 0 ? $otherContributionMinor : $otherContributionMinor, $currency),
                'customer_tip' => $this->currencyService->formatMinor($customerTipMinor, $currency),
                'allocated_tips' => $this->currencyService->formatMinor($allocatedTipsMinor, $currency),
                'recognized_net_revenue' => $this->currencyService->formatMinor($recognizedNetRevenueMinor, $currency),
                'total_profit' => $this->formatSignedMinor($totalProfitMinor, $currency),
            ],
        ];
    }

    protected function formatSignedMinor(int $minor, string $currency): string
    {
        if ($minor < 0) {
            return '-' . $this->currencyService->formatMinor(abs($minor), $currency);
        }

        return $this->currencyService->formatMinor($minor, $currency);
    }
}
