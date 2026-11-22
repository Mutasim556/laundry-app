<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Admin\Models\Setting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(\Modules\Admin\App\Console\RunScheduledDatabaseBackup::class)
    ->dailyAt('02:00')
    ->when(function () {
        try {
            $freq = Setting::get('db_backup_schedule_frequency', 'manual');
            if ($freq === 'daily') {
                return true;
            }
            if ($freq === 'weekly') {
                return now()->isSunday();
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    });
