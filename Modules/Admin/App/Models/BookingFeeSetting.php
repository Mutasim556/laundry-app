<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingFeeSetting extends Model
{
    public const ALLOWED_FEE_CODES = ['service_fee', 'collection_delivery_fee'];

    protected $table = 'booking_fee_settings';

    protected $fillable = [
        'fee_code',
        'display_name',
        'fee_type',
        'fee_value',
        'fee_value_minor',
        'is_active',
        'is_free',
        'threshold_enabled',
        'threshold_amount_minor',
        'threshold_after_discount',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'fee_value' => 'decimal:2',
        'fee_value_minor' => 'integer',
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'threshold_enabled' => 'boolean',
        'threshold_amount_minor' => 'integer',
        'threshold_after_discount' => 'boolean',
        'updated_by' => 'integer',
    ];

    public function scopeAllowed(Builder $query): Builder
    {
        return $query->whereIn('fee_code', self::ALLOWED_FEE_CODES);
    }

    public static function toMinor(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromMinor(int $minor): float
    {
        return round($minor / 100, 2);
    }

    public function getThresholdAmountAttribute(): float
    {
        return self::fromMinor((int) $this->threshold_amount_minor);
    }
}
