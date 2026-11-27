<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Admin;

class LaundryTagBatch extends Model
{
    protected $fillable = [
        'batch_reference',
        'label',
        'template',
        'printer_profile_id',
        'quantity',
        'code_from',
        'code_to',
        'idempotency_key',
        'generated_by_admin_id',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function printerProfile(): BelongsTo
    {
        return $this->belongsTo(TagPrinterProfile::class, 'printer_profile_id');
    }

    public function generatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'generated_by_admin_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(LaundryTag::class, 'batch_id')->orderBy('id');
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(LaundryTagPrintJob::class, 'batch_id')->orderByDesc('id');
    }
}
