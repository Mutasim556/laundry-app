<?php

namespace Modules\Admin\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admin\App\Models\DatabaseBackupOperation;
use Modules\Admin\App\Services\DatabaseBackupService;

class RestoreDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $operationId
    ) {}

    public function handle(DatabaseBackupService $service): void
    {
        $operation = DatabaseBackupOperation::find($this->operationId);
        if (! $operation) {
            return;
        }

        $service->executeRestore($operation, true);
    }
}
