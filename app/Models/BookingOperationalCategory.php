<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\ItemCategory;

class BookingOperationalCategory extends Model
{
    public const ORIGIN_CUSTOMER = 'customer';
    public const ORIGIN_ADMIN = 'admin';
    public const ORIGIN_DELIVERY_PERSON = 'delivery_person';
    public const ORIGIN_UNKNOWN = 'unknown';

    protected $fillable = [
        'booking_id',
        'item_category_id',
        'category_name',
        'origin_type',
        'origin_actor_id',
        'is_removed',
        'removed_by_type',
        'removed_by_id',
        'removed_at',
        'removal_reason',
    ];

    protected function casts(): array
    {
        return [
            'origin_actor_id' => 'integer',
            'is_removed' => 'boolean',
            'removed_by_id' => 'integer',
            'removed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }
}
