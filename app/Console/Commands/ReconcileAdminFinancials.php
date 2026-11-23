<?php

namespace App\Console\Commands;

use App\Services\AdminAccountService;
use Illuminate\Console\Command;

class ReconcileAdminFinancials extends Command
{
    protected $signature = 'admin-accounts:reconcile';

    protected $description = 'Read-only diagnostic checking completed bookings, delivery allocations, tip splits, store credits, and admin account balances.';

    public function handle(AdminAccountService $adminAccountService): int
    {
        $report = $adminAccountService->reconcileAll();

        $this->info("Checked {$report['checked_bookings']} completed booking(s) and {$report['checked_accounts']} admin account(s).");

        if (empty($report['issues'])) {
            $this->info('OK: No financial discrepancies found.');

            return self::SUCCESS;
        }

        $this->warn(count($report['issues']) . ' discrepancy(ies) detected:');
        $this->table(
            ['Type', 'Booking / Currency', 'Detail'],
            collect($report['issues'])->map(fn ($issue) => [
                $issue['type'],
                $issue['booking_reference'] ?? ($issue['currency'] ?? '-'),
                $issue['detail'],
            ])->all()
        );

        return self::FAILURE;
    }
}
