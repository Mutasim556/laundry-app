<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class DeliveryPersonCashTransaction extends Model
{
    public const TYPE_CUSTOMER_CASH_COLLECTED = 'customer_cash_collected';
    public const TYPE_CUSTOMER_TRANSFER_CONFIRMED = 'customer_transfer_received_confirmed';
    public const TYPE_CASH_HANDOVER_TO_ADMIN = 'cash_handover_to_admin';
    public const TYPE_ONLINE_FUNDS_SETTLEMENT = 'online_funds_settlement';
    public const TYPE_AUTHORIZED_ADJUSTMENT = 'authorized_adjustment';
    public const TYPE_REVERSAL = 'reversal';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    public const CHANNEL_CASH_IN_HAND = 'cash_in_hand';
    public const CHANNEL_ONLINE_TRANSFER = 'online_transfer';
    public const CHANNEL_ADMIN_SETTLEMENT = 'admin_settlement';
    public const CHANNEL_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'delivery_person_id',
        'booking_id',
        'settlement_id',
        'transaction_type',
        'direction',
        'amount',
        'currency',
        'balance_after',
        'payment_channel',
        'reference',
        'idempotency_key',
        'related_transaction_id',
        'description',
        'created_by_type',
        'created_by_id',
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
            throw new RuntimeException('Delivery person cash custody transactions are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new RuntimeException('Delivery person cash custody transactions are immutable and cannot be deleted.');
        });
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonSettlement::class, 'settlement_id');
    }
}
