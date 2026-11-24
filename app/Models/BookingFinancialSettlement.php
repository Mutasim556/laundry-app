<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class BookingFinancialSettlement extends Model
{
    protected $fillable = [
        'booking_id',
        'revision_number',
        'is_current',
        'currency',
        'delivery_fee_minor',
        'customer_tip_minor',
        'collection_delivery_payment_minor',
        'final_delivery_payment_minor',
        'collection_tip_share_minor',
        'final_tip_share_minor',
        'admin_delivery_margin_minor',
        'store_net_earnings_minor',
        'advance_received_minor',
        'remaining_due_minor',
        'settlement_status',
        'settled_by_admin_id',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'is_current' => 'boolean',
            'delivery_fee_minor' => 'integer',
            'customer_tip_minor' => 'integer',
            'collection_delivery_payment_minor' => 'integer',
            'final_delivery_payment_minor' => 'integer',
            'collection_tip_share_minor' => 'integer',
            'final_tip_share_minor' => 'integer',
            'admin_delivery_margin_minor' => 'integer',
            'store_net_earnings_minor' => 'integer',
            'advance_received_minor' => 'integer',
            'remaining_due_minor' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function settledByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'settled_by_admin_id');
    }
}
