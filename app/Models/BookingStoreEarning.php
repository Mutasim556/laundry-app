<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Store;

class BookingStoreEarning extends Model
{
    protected $fillable = [
        'booking_id',
        'store_id',
        'booking_store_assignment_id',
        'currency',
        'eligible_service_amount_minor',
        'commission_type',
        'commission_rate',
        'commission_amount_minor',
        'net_store_earnings_minor',
        'is_current',
        'snapshot_hash',
        'finalized_by_admin_id',
        'finalized_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'eligible_service_amount_minor' => 'integer',
            'commission_rate' => 'decimal:2',
            'commission_amount_minor' => 'integer',
            'net_store_earnings_minor' => 'integer',
            'is_current' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function storeAssignment(): BelongsTo
    {
        return $this->belongsTo(BookingStoreAssignment::class, 'booking_store_assignment_id');
    }
}
