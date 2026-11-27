<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class LaundryTagPrintJob extends Model
{
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'print_reference',
        'batch_id',
        'printer_profile_id',
        'tag_ids',
        'tag_count',
        'copies',
        'start_offset',
        'is_reprint',
        'status',
        'requested_by_admin_id',
        'prepared_at',
        'requested_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'tag_ids' => 'array',
            'tag_count' => 'integer',
            'copies' => 'integer',
            'start_offset' => 'integer',
            'is_reprint' => 'boolean',
            'prepared_at' => 'datetime',
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LaundryTagBatch::class, 'batch_id');
    }

    public function printerProfile(): BelongsTo
    {
        return $this->belongsTo(TagPrinterProfile::class, 'printer_profile_id');
    }

    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }
}
