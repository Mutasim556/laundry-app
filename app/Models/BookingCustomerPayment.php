<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class BookingCustomerPayment extends Model
{
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_CASH_IN_HAND = 'cash_in_hand';
    public const METHOD_ONLINE_TRANSFER = 'online_transfer';

    public const CHANNEL_GATEWAY = 'gateway';
    public const CHANNEL_CASH = 'cash';
    public const CHANNEL_BANK_TRANSFER = 'bank_transfer';

    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'booking_id',
        'delivery_person_id',
        'payment_method',
        'payment_channel',
        'amount_minor',
        'currency',
        'status',
        'external_reference',
        'receiving_account',
        'proof_path',
        'idempotency_key',
        'notes',
        'collected_at',
        'verified_by_admin_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'collected_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }
}
