<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class RefundTransaction extends Model
{
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_COD_MANUAL = 'cod_manual';
    public const METHOD_ONLINE_TRANSFER = 'online_transfer';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_AWAITING_PAYOUT = 'awaiting_payout';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RECONCILED = 'reconciled';

    protected $fillable = [
        'refund_reference',
        'claim_id',
        'booking_id',
        'booking_customer_payment_id',
        'payment_method',
        'amount_minor',
        'currency',
        'status',
        'provider_refund_id',
        'idempotency_key',
        'payout_recipient',
        'payout_method',
        'external_reference',
        'approved_by_admin_id',
        'processed_by_admin_id',
        'processed_at',
        'reconciled_at',
        'failure_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'processed_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceClaim::class, 'claim_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(BookingCustomerPayment::class, 'booking_customer_payment_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }

    public function isCompletedDisbursement(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCEEDED, self::STATUS_PAID, self::STATUS_RECONCILED], true);
    }
}
