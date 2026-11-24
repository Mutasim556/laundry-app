<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseCleanupLog extends Model
{
    protected $table = 'database_cleanup_logs';

    protected $fillable = [
        'operation_uuid',
        'preview_id',
        'idempotency_key',
        'module',
        'cleanup_mode',
        'admin_id',
        'admin_name',
        'environment',
        'database_name',
        'backup_id',
        'backup_reference',
        'targeted_bookings_count',
        'blocked_bookings_count',
        'total_deleted_count',
        'deleted_per_table',
        'post_verification',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'deleted_per_table' => 'array',
        'post_verification' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
