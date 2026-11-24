<?php

namespace Modules\Admin\App\Console;

use App\Models\Booking;
use App\Models\StoreTransaction;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use App\Services\StoreEarningsService;
use Illuminate\Console\Command;

class BackfillCompletedStoreEarnings extends Command
{
    protected $signature = 'store-accounts:backfill-completed {--dry-run : Preview eligible bookings without posting credits} {--force : Execute credit posting for eligible completed bookings}';

    protected $description = 'Safely backfill store account credits for historical Completed bookings (dry-run by default).';

    public function handle(
        StoreAccountService $accountService,
        StoreEarningsService $earningsService,
        CurrencyService $currencyService
    ): int {
        $dryRun = ! $this->option('force') || $this->option('dry-run');
        $this->info($dryRun ? 'Running in DRY-RUN mode (no changes will be posted).' : 'Running in LIVE BACKFILL mode.');

        $completedBookings = Booking::query()
            ->where('booking_status', Booking::STATUS_COMPLETED)
            ->with(['currentStoreAssignment.store', 'currentStoreEarning'])
            ->orderBy('id')
            ->get();

        $posted = 0;
        $skipped = 0;

        foreach ($completedBookings as $booking) {
            $alreadyPosted = StoreTransaction::where('booking_id', $booking->id)
                ->where('transaction_type', StoreTransaction::TYPE_BOOKING_EARNING)
                ->exists();

            if ($alreadyPosted) {
                $skipped++;
                continue;
            }

            $store = $booking->currentStoreAssignment?->store;
            if (! $store || $store->commission_rate === null || ! $booking->isInvoiceEligible($currencyService)) {
                $this->warn("SKIP {$booking->reference}: missing valid store assignment, commission rate, or Finalized stage.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $pricing = $booking->getOperationalPricingSummary($currencyService, false);
                $eligible = (int) $pricing['items_subtotal_minor'];
                $rate = (float) $store->commission_rate;
                $comm = (int) round($eligible * ($rate / 100));
                $net = max(0, $eligible - $comm);
                $this->line("ELIGIBLE {$booking->reference} -> Store #{$store->id} ({$store->name}): net " . StoreAccountService::minorToDecimal($net) . " {$booking->currency} @ {$rate}%");
                $posted++;
                continue;
            }

            $earningsService->getOrCreateSnapshotForBooking($booking);
            $tx = $accountService->creditCompletedBooking($booking);
            if ($tx) {
                $this->info("POSTED {$booking->reference} -> {$tx->reference} ({$tx->currency} {$tx->amount})");
                $posted++;
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Eligible/Posted: {$posted}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
