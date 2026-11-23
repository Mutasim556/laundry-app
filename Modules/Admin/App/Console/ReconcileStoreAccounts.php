<?php

namespace Modules\Admin\App\Console;

use App\Services\StoreAccountService;
use Illuminate\Console\Command;

class ReconcileStoreAccounts extends Command
{
    protected $signature = 'store-accounts:reconcile';

    protected $description = 'Audit store account balances against immutable store_transactions and completed bookings.';

    public function handle(StoreAccountService $accountService): int
    {
        $report = $accountService->reconcileAll();

        if ($report['ok']) {
            $this->info('Reconciliation OK: all store account balances and completed booking credits match.');

            return self::SUCCESS;
        }

        foreach ($report['issues'] as $issue) {
            $this->error($issue);
        }

        return self::FAILURE;
    }
}
