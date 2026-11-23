<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingFinancialSettlementService;
use Illuminate\Console\Command;
use Throwable;

class BackfillAdminSettlements extends Command
{
    protected $signature = 'admin-accounts:backfill-completed {--dry-run : Preview bookings without writing changes} {--force : Skip confirmation prompt}';

    protected $description = 'Safely backfill financial settlements and delivery/store/admin ledger entries for historical completed bookings.';

    public function handle(BookingFinancialSettlementService $settlementService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm('This will post missing financial settlements and ledger entries for completed bookings. Continue?')) {
                return self::INVALID;
            }
        }

        $bookings = Booking::query()
            ->where('booking_status', Booking::STATUS_COMPLETED)
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->get();

        $settled = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($bookings as $booking) {
            if ($booking->currentFinancialSettlement && $booking->currentFinancialSettlement->settlement_status === 'settled') {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY RUN] Would settle {$booking->reference}");
                $settled++;
                continue;
            }

            try {
                $settlementService->settleCompletedBooking($booking, null);
                $this->info("Settled {$booking->reference}");
                $settled++;
            } catch (Throwable $e) {
                $this->error("Skipped {$booking->reference}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Summary: settled={$settled}, skipped={$skipped}, errors={$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
