<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;
use RuntimeException;

class StoreTransaction extends Model
{
    public const TYPE_BOOKING_EARNING = 'booking_earning';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_POSITIVE_ADJUSTMENT = 'positive_adjustment';
    public const TYPE_NEGATIVE_ADJUSTMENT = 'negative_adjustment';
    public const TYPE_REVERSAL = 'reversal';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    protected $fillable = [
        'store_id',
        'booking_id',
        'booking_store_earning_id',
        'transaction_type',
        'direction',
        'amount',
        'currency',
        'balance_after',
        'reference',
        'idempotency_key',
        'related_transaction_id',
        'description',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Store transactions are immutable financial audit records and cannot be modified.');
        });

        static::deleting(function () {
            throw new RuntimeException('Store transactions are immutable financial audit records and cannot be deleted.');
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function earning(): BelongsTo
    {
        return $this->belongsTo(BookingStoreEarning::class, 'booking_store_earning_id');
    }

    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_BOOKING_EARNING => 'Booking Earning',
            self::TYPE_PAYOUT => 'Payout',
            self::TYPE_POSITIVE_ADJUSTMENT => 'Positive Adjustment',
            self::TYPE_NEGATIVE_ADJUSTMENT => 'Negative Adjustment',
            self::TYPE_REVERSAL => 'Reversal',
            default => ucfirst(str_replace('_', ' ', (string) $this->transaction_type)),
        };
    }
}
