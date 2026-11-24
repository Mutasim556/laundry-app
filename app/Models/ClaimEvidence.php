<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimEvidence extends Model
{
    protected $table = 'claim_evidence';

    protected $fillable = [
        'claim_id',
        'uploaded_by_type',
        'uploaded_by_id',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceClaim::class, 'claim_id');
    }
}
