<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStoreEarning;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Store;

class StoreEarningsService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function isBookingFinanciallyVisibleToStore(Booking $booking, Store $store): bool
    {
        if (! $booking->isInvoiceEligible($this->currencyService)) {
            return false;
        }

        $assignment = $booking->currentStoreAssignment;
        if (! $assignment || (int) $assignment->store_id !== (int) $store->id) {
            return false;
        }

        return $store->commission_rate !== null;
    }

    public function getOrCreateSnapshotForBooking(Booking $booking, ?int $adminId = null): ?BookingStoreEarning
    {
        $booking->loadMissing(['currentStoreAssignment.store', 'currentStoreEarning']);
        $assignment = $booking->currentStoreAssignment;
        $store = $assignment?->store;

        if (! $assignment || ! $store || ! $this->isBookingFinanciallyVisibleToStore($booking, $store)) {
            return null;
        }

        $pricing = $booking->getOperationalPricingSummary($this->currencyService, false);
        $eligibleServiceMinor = (int) $pricing['items_subtotal_minor'];

        // If a current snapshot already exists for this booking + store + eligible base, preserve its captured commission rate
        $existing = $booking->currentStoreEarning;
        if (
            $existing
            && (int) $existing->store_id === (int) $store->id
            && (int) $existing->eligible_service_amount_minor === $eligibleServiceMinor
            && $existing->currency === $booking->currency
        ) {
            return $existing;
        }

        return DB::transaction(function () use ($booking, $assignment, $store, $eligibleServiceMinor, $adminId) {
            /** @var Booking $locked */
            $locked = Booking::query()
                ->with(['currentStoreAssignment.store', 'currentStoreEarning'])
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAssignment = $locked->currentStoreAssignment;
            $lockedStore = $lockedAssignment?->store;
            if (! $lockedAssignment || ! $lockedStore || ! $this->isBookingFinanciallyVisibleToStore($locked, $lockedStore)) {
                return null;
            }

            $lockedPricing = $locked->getOperationalPricingSummary($this->currencyService, false);
            $lockedEligibleMinor = (int) $lockedPricing['items_subtotal_minor'];

            $current = $locked->currentStoreEarning;
            if (
                $current
                && (int) $current->store_id === (int) $lockedStore->id
                && (int) $current->eligible_service_amount_minor === $lockedEligibleMinor
                && $current->currency === $locked->currency
            ) {
                return $current;
            }

            $commissionRate = max(0.0, min(100.0, (float) $lockedStore->commission_rate));
            $commissionAmountMinor = (int) round($lockedEligibleMinor * ($commissionRate / 100));
            $netStoreMinor = max(0, $lockedEligibleMinor - $commissionAmountMinor);

            $snapshotPayload = [
                'booking_id' => $locked->id,
                'store_id' => $lockedStore->id,
                'currency' => $locked->currency,
                'eligible_service_amount_minor' => $lockedEligibleMinor,
                'commission_rate' => number_format($commissionRate, 2, '.', ''),
                'commission_amount_minor' => $commissionAmountMinor,
                'net_store_earnings_minor' => $netStoreMinor,
            ];
            $snapshotHash = hash('sha256', json_encode($snapshotPayload, JSON_UNESCAPED_SLASHES));

            if ($current) {
                $current->update(['is_current' => false]);
            }

            return BookingStoreEarning::create([
                'booking_id' => $locked->id,
                'store_id' => $lockedStore->id,
                'booking_store_assignment_id' => $lockedAssignment->id,
                'currency' => $locked->currency,
                'eligible_service_amount_minor' => $lockedEligibleMinor,
                'commission_type' => $lockedStore->commission_type ?: 'percentage',
                'commission_rate' => $commissionRate,
                'commission_amount_minor' => $commissionAmountMinor,
                'net_store_earnings_minor' => $netStoreMinor,
                'is_current' => true,
                'snapshot_hash' => $snapshotHash,
                'finalized_by_admin_id' => $adminId,
                'finalized_at' => now(),
                'notes' => $current ? 'Revised snapshot after authorized booking/store update.' : null,
            ]);
        });
    }
}
