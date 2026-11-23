<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class BookingOperationalItem extends Model
{
    protected $table = 'booking_operational_items';

    protected $fillable = [
        'booking_id',
        'item_category_id',
        'service_id',
        'item_id',
        'service_name',
        'category_name',
        'item_name',
        'pricing_mode',
        'unit_name',
        'quantity',
        'weight_kg',
        'catalog_unit_price_minor',
        'actual_unit_price_minor',
        'line_total_minor',
        'is_price_overridden',
        'origin_type',
        'origin_actor_id',
        'adjustment_reason',
        'adjusted_by_admin_id',
        'adjusted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight_kg' => 'decimal:3',
            'catalog_unit_price_minor' => 'integer',
            'actual_unit_price_minor' => 'integer',
            'line_total_minor' => 'integer',
            'is_price_overridden' => 'boolean',
            'adjusted_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function computeLineTotalMinor(): int
    {
        if ($this->pricing_mode === 'weight') {
            $weight = (float) ($this->weight_kg ?? 0);
            if ($weight <= 0) {
                return 0;
            }

            return (int) round($weight * $this->actual_unit_price_minor);
        }

        return max(0, (int) $this->quantity) * max(0, (int) $this->actual_unit_price_minor);
    }
}
