<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingFinancialSettlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\DeliveryAssignment;
use RuntimeException;

class BookingFinancialSettlementService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected StoreEarningsService $storeEarningsService,
        protected StoreAccountService $storeAccountService,
        protected AdminAccountService $adminAccountService
    ) {}

    /**
     * Deterministically split minor units 50/50; any odd minor unit goes to Final Delivery.
     *
     * @return array{collection: int, final: int}
     */
    public static function splitMinorHalf(int $totalMinor): array
    {
        $safeTotal = max(0, $totalMinor);
        $collection = intdiv($safeTotal, 2);
        $final = $safeTotal - $collection;

        return [
            'collection' => $collection,
            'final' => $final,
        ];
    }

    /**
     * Build live allocation & settlement summary for admin Booking Details and assignment modals.
     *
     * @return array<string, mixed>
     */
    public function buildPreview(Booking $booking): array
    {
        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
        $deliveryFeeMinor = max(0, (int) ($booking->collection_delivery_fee_minor ?? 0));
        $customerTipMinor = max(0, (int) ($booking->tip_amount_minor ?? 0));

        $defaultDeliverySplit = self::splitMinorHalf($deliveryFeeMinor);
        $tipSplit = self::splitMinorHalf($customerTipMinor);

        $collectionAssignment = $booking->relationLoaded('currentCollectionAssignment')
            ? $booking->currentCollectionAssignment
            : $booking->currentCollectionAssignment()->first();

        $finalAssignment = $booking->relationLoaded('currentFinalDeliveryAssignment')
            ? $booking->currentFinalDeliveryAssignment
            : $booking->currentFinalDeliveryAssignment()->first();

        $collectionPaymentMinor = $collectionAssignment
            ? (int) $collectionAssignment->rate_minor
            : $defaultDeliverySplit['collection'];

        $finalPaymentMinor = $finalAssignment
            ? (int) $finalAssignment->rate_minor
            : $defaultDeliverySplit['final'];

        $collectionTipMinor = $tipSplit['collection'];
        $finalTipMinor = $tipSplit['final'];

        $totalDeliveryPayoutMinor = $collectionPaymentMinor + $finalPaymentMinor;
        $adminDeliveryMarginMinor = $deliveryFeeMinor - $totalDeliveryPayoutMinor;

        $totalBookingMinor = max(0, (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0));
        $refundedMinor = max(0, (int) ($booking->refunded_amount_minor ?? 0));
        $isAdvancePaid = in_array((string) $booking->payment_status, ['paid', 'authorized'], true);
        $advanceReceivedMinor = $isAdvancePaid ? max(0, $totalBookingMinor - $refundedMinor) : 0;
        $remainingDueMinor = max(0, $totalBookingMinor - $advanceReceivedMinor);

        $settlement = $booking->relationLoaded('currentFinancialSettlement')
            ? $booking->currentFinancialSettlement
            : $booking->currentFinancialSettlement()->first();

        $isCompleted = $booking->booking_status === Booking::STATUS_COMPLETED
            && in_array(Booking::STATUS_COMPLETED, $booking->getCompletedOperationalStatuses(), true);

        if ($settlement && $settlement->settlement_status === 'settled') {
            $financialStatus = 'Settled';
            $financialStatusBadge = 'success';
        } elseif ($booking->cancelled_at !== null) {
            $financialStatus = 'Cancelled';
            $financialStatusBadge = 'secondary';
        } elseif ($isCompleted) {
            $financialStatus = 'Pending Settlement';
            $financialStatusBadge = 'warning';
        } else {
            $financialStatus = 'Provisional (Settles on Completed)';
            $financialStatusBadge = 'info';
        }

        if (! $isAdvancePaid) {
            $advanceStatus = 'No Advance Paid';
            $advanceBadge = 'secondary';
        } elseif ($isCompleted && $settlement) {
            $advanceStatus = 'Advance Applied at Completion';
            $advanceBadge = 'success';
        } else {
            $advanceStatus = 'Awaiting Advance Settlement';
            $advanceBadge = 'warning';
        }

        $storeEarning = $booking->relationLoaded('currentStoreEarning')
            ? $booking->currentStoreEarning
            : $booking->currentStoreEarning()->first();

        return [
            'currency' => $currency,
            'delivery_fee_minor' => $deliveryFeeMinor,
            'customer_tip_minor' => $customerTipMinor,
            'default_collection_payment_minor' => $defaultDeliverySplit['collection'],
            'default_final_payment_minor' => $defaultDeliverySplit['final'],
            'collection_payment_minor' => $collectionPaymentMinor,
            'final_payment_minor' => $finalPaymentMinor,
            'collection_tip_share_minor' => $collectionTipMinor,
            'final_tip_share_minor' => $finalTipMinor,
            'collection_total_earnings_minor' => $collectionPaymentMinor + $collectionTipMinor,
            'final_total_earnings_minor' => $finalPaymentMinor + $finalTipMinor,
            'total_delivery_payout_minor' => $totalDeliveryPayoutMinor,
            'total_delivery_earnings_with_tips_minor' => $totalDeliveryPayoutMinor + $customerTipMinor,
            'admin_delivery_margin_minor' => $adminDeliveryMarginMinor,
            'store_net_earnings_minor' => $storeEarning
                ? (int) $storeEarning->net_store_earnings_minor
                : 0,
            'advance_received_minor' => $advanceReceivedMinor,
            'remaining_due_minor' => $remainingDueMinor,
            'advance_status' => $advanceStatus,
            'advance_badge' => $advanceBadge,
            'financial_status' => $financialStatus,
            'financial_status_badge' => $financialStatusBadge,
            'is_settled' => $settlement !== null && $settlement->settlement_status === 'settled',
            'settlement' => $settlement,
        ];
    }

    /**
     * Single atomic settlement entry point when booking reaches final Completed stage.
     */
    public function settleCompletedBooking(Booking $booking, ?int $adminId = null): ?BookingFinancialSettlement
    {
        return DB::transaction(function () use ($booking, $adminId) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at !== null) {
                throw new RuntimeException("Cannot settle cancelled booking {$locked->reference}.");
            }

            $completedStages = $locked->getCompletedOperationalStatuses();
            if ($locked->booking_status !== Booking::STATUS_COMPLETED || ! in_array(Booking::STATUS_COMPLETED, $completedStages, true)) {
                return null;
            }

            if (! in_array(Booking::STATUS_FINALIZED, $completedStages, true) || ! $locked->isInvoiceEligible($this->currencyService)) {
                return null;
            }

            $storeEarning = $locked->currentStoreAssignment
                ? $this->storeEarningsService->getOrCreateSnapshotForBooking($locked, $adminId)
                : null;

            $collectionAssignment = $locked->currentCollectionAssignment()->lockForUpdate()->first();
            $finalAssignment = $locked->currentFinalDeliveryAssignment()->lockForUpdate()->first();

            if (! $storeEarning && ! $collectionAssignment && ! $finalAssignment) {
                return null;
            }

            $currency = strtoupper((string) ($locked->currency ?: $this->currencyService->code()));
            $deliveryFeeMinor = max(0, (int) ($locked->collection_delivery_fee_minor ?? 0));
            $customerTipMinor = max(0, (int) ($locked->tip_amount_minor ?? 0));
            $tipSplit = self::splitMinorHalf($customerTipMinor);

            $collectionPaymentMinor = $collectionAssignment ? max(0, (int) $collectionAssignment->rate_minor) : 0;
            $finalPaymentMinor = $finalAssignment ? max(0, (int) $finalAssignment->rate_minor) : 0;
            $adminMarginMinor = ($collectionAssignment || $finalAssignment)
                ? ($deliveryFeeMinor - ($collectionPaymentMinor + $finalPaymentMinor))
                : 0;
            $storeNetMinor = $storeEarning ? (int) $storeEarning->net_store_earnings_minor : 0;

            $totalBookingMinor = max(0, (int) ($locked->final_amount_minor ?? $locked->estimated_amount_minor ?? 0));
            $refundedMinor = max(0, (int) ($locked->refunded_amount_minor ?? 0));
            $isAdvancePaid = in_array((string) $locked->payment_status, ['paid', 'authorized'], true);
            $advanceMinor = $isAdvancePaid ? max(0, $totalBookingMinor - $refundedMinor) : 0;
            $remainingDueMinor = max(0, $totalBookingMinor - $advanceMinor);

            $existingCurrent = BookingFinancialSettlement::query()
                ->where('booking_id', $locked->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($existingCurrent && $existingCurrent->settlement_status === 'settled') {
                $this->ensurePostingsForSettlement(
                    $locked,
                    $collectionAssignment,
                    $finalAssignment,
                    $existingCurrent,
                    $adminId
                );

                return $existingCurrent;
            }

            $nextRevision = ((int) BookingFinancialSettlement::query()
                ->where('booking_id', $locked->id)
                ->max('revision_number')) + 1;

            BookingFinancialSettlement::query()
                ->where('booking_id', $locked->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $settlement = BookingFinancialSettlement::query()->create([
                'booking_id' => $locked->id,
                'revision_number' => $nextRevision,
                'is_current' => true,
                'currency' => $currency,
                'delivery_fee_minor' => $deliveryFeeMinor,
                'customer_tip_minor' => $customerTipMinor,
                'collection_delivery_payment_minor' => $collectionPaymentMinor,
                'final_delivery_payment_minor' => $finalPaymentMinor,
                'collection_tip_share_minor' => $tipSplit['collection'],
                'final_tip_share_minor' => $tipSplit['final'],
                'admin_delivery_margin_minor' => $adminMarginMinor,
                'store_net_earnings_minor' => $storeNetMinor,
                'advance_received_minor' => $advanceMinor,
                'remaining_due_minor' => $remainingDueMinor,
                'settlement_status' => 'settled',
                'settled_by_admin_id' => $adminId,
                'settled_at' => now(),
            ]);

            $this->ensurePostingsForSettlement(
                $locked,
                $collectionAssignment,
                $finalAssignment,
                $settlement,
                $adminId
            );

            return $settlement;
        });
    }

    protected function ensurePostingsForSettlement(
        Booking $booking,
        ?DeliveryAssignment $collectionAssignment,
        ?DeliveryAssignment $finalAssignment,
        BookingFinancialSettlement $settlement,
        ?int $adminId
    ): void {
        // 1. Sync tip shares and post Collection Delivery Person ledger credit (if not already credited at Store Handover Acceptance)
        if ($collectionAssignment && $collectionAssignment->earnings_status !== 'credited') {
            $this->creditDeliveryAssignment(
                $booking,
                $collectionAssignment,
                (int) $settlement->collection_tip_share_minor,
                "booking:{$booking->id}:collection-earning:{$collectionAssignment->id}",
                'Collection delivery payment and tip share for booking ' . $booking->reference,
                $adminId
            );
        }

        // 2. Sync tip shares and post Final Delivery Person ledger credit
        if ($finalAssignment) {
            $this->creditDeliveryAssignment(
                $booking,
                $finalAssignment,
                (int) $settlement->final_tip_share_minor,
                "booking:{$booking->id}:final-earning:{$finalAssignment->id}",
                'Final delivery payment and tip share for booking ' . $booking->reference,
                $adminId
            );
        }

        // 3. Credit Store Current Account (idempotent)
        if ($booking->currentStoreAssignment) {
            $this->storeAccountService->creditCompletedBooking($booking, $adminId);
        }

        // 4. Post Admin Delivery Margin or Shortfall (skipped cleanly if margin === 0)
        $this->adminAccountService->postDeliveryMarginOrShortfall(
            $booking,
            (int) $settlement->admin_delivery_margin_minor,
            $settlement->id,
            $adminId
        );
    }

    public function creditDeliveryAssignment(
        Booking $booking,
        DeliveryAssignment $assignment,
        int $tipShareMinor,
        string $idempotencyKey,
        string $notes,
        ?int $adminId = null
    ): void {
        $assignment->update([
            'tip_share_minor' => $tipShareMinor,
            'earnings_status' => 'credited',
        ]);

        $exists = DB::table('delivery_person_ledger_entries')
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($exists) {
            return;
        }

        $totalMinor = max(0, (int) $assignment->rate_minor) + max(0, $tipShareMinor);

        DB::table('delivery_person_ledger_entries')->insert([
            'entry_reference' => 'DPL-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'idempotency_key' => $idempotencyKey,
            'delivery_person_id' => $assignment->delivery_person_id,
            'delivery_assignment_id' => $assignment->id,
            'booking_id' => $booking->id,
            'transaction_type' => 'assignment_earning',
            'direction' => 'credit',
            'amount_minor' => $totalMinor,
            'currency' => $assignment->currency ?: ($booking->currency ?: $this->currencyService->code()),
            'notes' => $notes,
            'created_by_admin_id' => $adminId,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
