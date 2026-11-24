<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceClaimItem extends Model
{
    protected $fillable = [
        'claim_id',
        'booking_operational_item_id',
        'service_name_snapshot',
        'affected_quantity',
        'claimed_amount_minor',
        'approved_amount_minor',
        'resolution_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'affected_quantity' => 'integer',
            'claimed_amount_minor' => 'integer',
            'approved_amount_minor' => 'integer',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceClaim::class, 'claim_id');
    }

    public function operationalItem(): BelongsTo
    {
        return $this->belongsTo(BookingOperationalItem::class, 'booking_operational_item_id');
    }
}
