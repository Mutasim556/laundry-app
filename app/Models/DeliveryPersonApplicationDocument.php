<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'document_type',
        'original_name',
        'disk',
        'storage_path',
        'mime_type',
        'file_size',
        'is_active',
        'replaced_by_id',
        'uploaded_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonApplication::class, 'application_id');
    }
}
