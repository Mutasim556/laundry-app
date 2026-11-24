<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInvoice extends Model
{
    protected $fillable = [
        'booking_id',
        'invoice_number',
        'revision_number',
        'is_current',
        'currency',
        'items_subtotal_minor',
        'service_fee_minor',
        'delivery_fee_minor',
        'tip_minor',
        'tax_minor',
        'discount_minor',
        'final_total_minor',
        'payment_gateway',
        'payment_status',
        'pricing_status_label',
        'business_snapshot',
        'customer_snapshot',
        'items_snapshot',
        'snapshot_hash',
        'issued_by_admin_id',
        'issued_at',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'is_current' => 'boolean',
        'items_subtotal_minor' => 'integer',
        'service_fee_minor' => 'integer',
        'delivery_fee_minor' => 'integer',
        'tip_minor' => 'integer',
        'tax_minor' => 'integer',
        'discount_minor' => 'integer',
        'final_total_minor' => 'integer',
        'business_snapshot' => 'array',
        'customer_snapshot' => 'array',
        'items_snapshot' => 'array',
        'issued_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
