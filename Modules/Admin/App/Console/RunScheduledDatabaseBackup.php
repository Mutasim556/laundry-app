<?php

namespace Modules\Admin\App\Console;

use Illuminate\Console\Command;
use Modules\Admin\App\Services\DatabaseBackupService;
use Modules\Admin\Models\Setting;

class RunScheduledDatabaseBackup extends Command
{
    protected $signature = 'db-backups:run-scheduled';

    protected $description = 'Create a scheduled database backup and prune expired backups per retention policy.';

    public function handle(DatabaseBackupService $service): int
    {
        $frequency = (string) Setting::get('db_backup_schedule_frequency', 'manual');
        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            $this->info('Scheduled database backups are set to Manual Only.');

            return self::SUCCESS;
        }

        $backup = $service->createBackup(null, 'scheduled', null, 'Laravel Scheduler');
        $keepCount = (int) Setting::get('db_backup_keep_count', config('database_backups.retention_keep_count', 10));
        $keepDays = (int) Setting::get('db_backup_keep_days', config('database_backups.retention_keep_days', 30));
        $maxStorageMb = (int) Setting::get('db_backup_max_storage_mb', config('database_backups.max_storage_mb', 1024));

        $pruned = $service->pruneOldBackups($keepCount, $keepDays, $maxStorageMb);

        $this->info("Scheduled backup created: {$backup->filename}. Pruned {$pruned} old backup(s).");

        return self::SUCCESS;
    }
}
