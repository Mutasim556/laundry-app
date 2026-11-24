<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Admin;

class DatabaseBackup extends Model
{
    protected $fillable = [
        'filename',
        'storage_path',
        'database_name',
        'db_engine',
        'db_version',
        'db_charset',
        'db_collation',
        'backup_type',
        'backup_method',
        'file_size_bytes',
        'checksum_sha256',
        'is_encrypted',
        'backup_status',
        'verification_status',
        'verified_at',
        'verification_notes',
        'source_environment',
        'created_by_admin_id',
        'is_protected',
        'notes',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'is_encrypted' => 'boolean',
        'is_protected' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(DatabaseBackupOperation::class, 'database_backup_id')->orderByDesc('id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->backup_type) {
            'pre_restore_safety' => 'Pre-Restore Safety',
            'uploaded' => 'Uploaded',
            'scheduled' => 'Scheduled',
            default => 'Full Backup',
        };
    }

    public function getVerificationLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'archive_integrity_verified' => 'Archive Integrity Verified',
            'restore_tested' => 'Restore Successfully Tested',
            'failed' => 'Verification Failed',
            default => 'Unverified',
        };
    }
}
