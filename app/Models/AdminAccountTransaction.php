<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use RuntimeException;

class AdminAccountTransaction extends Model
{
    public const TYPE_DELIVERY_MARGIN_CREDIT = 'delivery_margin_credit';
    public const TYPE_DELIVERY_SHORTFALL_DEBIT = 'delivery_shortfall_debit';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_ADVANCE_SETTLEMENT = 'advance_settlement';
    public const TYPE_REVERSAL = 'reversal';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    protected $fillable = [
        'admin_account_id',
        'booking_id',
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
            throw new RuntimeException('Admin account transactions are immutable audit records and cannot be modified.');
        });

        static::deleting(function () {
            throw new RuntimeException('Admin account transactions are immutable audit records and cannot be deleted.');
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AdminAccount::class, 'admin_account_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
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
            self::TYPE_DELIVERY_MARGIN_CREDIT => 'Delivery Margin Credit',
            self::TYPE_DELIVERY_SHORTFALL_DEBIT => 'Delivery Shortfall Debit',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_ADVANCE_SETTLEMENT => 'Advance Settlement',
            self::TYPE_REVERSAL => 'Reversal',
            default => ucfirst(str_replace('_', ' ', (string) $this->transaction_type)),
        };
    }
}
