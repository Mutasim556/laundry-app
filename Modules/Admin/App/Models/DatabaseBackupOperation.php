<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class DatabaseBackupOperation extends Model
{
    protected $fillable = [
        'admin_id',
        'operation_type',
        'database_backup_id',
        'safety_backup_id',
        'source_database',
        'target_database',
        'operation_status',
        'verification_result',
        'failure_summary',
        'ip_address',
        'user_agent',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(DatabaseBackup::class, 'database_backup_id');
    }

    public function safetyBackup(): BelongsTo
    {
        return $this->belongsTo(DatabaseBackup::class, 'safety_backup_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->operation_status) {
            'pending' => 'Pending',
            'validating' => 'Validating',
            'creating_safety_backup' => 'Creating Safety Backup',
            'restoring' => 'Restoring',
            'verifying' => 'Verifying',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst((string) $this->operation_status),
        };
    }
}
