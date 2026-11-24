<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;

class StoreWithdrawalRequest extends Model
{
    protected $table = 'store_withdrawal_requests';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MANUALLY_SETTLED = 'manually_settled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'withdrawal_reference',
        'store_id',
        'requested_by_store_user_id',
        'bank_account_id',
        'bank_snapshot_json',
        'amount_minor',
        'currency',
        'request_month',
        'available_balance_before_minor',
        'status',
        'notes',
        'idempotency_key',
        'reservation_transaction_id',
        'release_transaction_id',
        'requested_at',
        'approved_by_admin_id',
        'approved_at',
        'rejected_by_admin_id',
        'rejected_at',
        'rejection_reason',
        'settled_by_admin_id',
        'settled_at',
        'payment_date',
        'external_reference',
        'settlement_notes',
        'audit_history',
    ];

    protected $casts = [
        'bank_snapshot_json' => 'array',
        'amount_minor' => 'integer',
        'available_balance_before_minor' => 'integer',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'settled_at' => 'datetime',
        'payment_date' => 'date',
        'audit_history' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'requested_by_store_user_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(StoreBankAccount::class, 'bank_account_id');
    }

    public function reservationTransaction(): BelongsTo
    {
        return $this->belongsTo(StoreTransaction::class, 'reservation_transaction_id');
    }

    public function releaseTransaction(): BelongsTo
    {
        return $this->belongsTo(StoreTransaction::class, 'release_transaction_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function rejectedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'rejected_by_admin_id');
    }

    public function settledByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'settled_by_admin_id');
    }

    public function statusMeta(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => ['label' => 'Pending', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'hex' => '#F59E0B'],
            self::STATUS_APPROVED => ['label' => 'Approved', 'badge' => 'bg-primary-subtle text-primary border border-primary-subtle', 'hex' => '#3B82F6'],
            self::STATUS_REJECTED => ['label' => 'Rejected', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle', 'hex' => '#EF4444'],
            self::STATUS_MANUALLY_SETTLED => ['label' => 'Manually Settled', 'badge' => 'bg-success-subtle text-success border border-success-subtle', 'hex' => '#16A34A'],
            self::STATUS_CANCELLED => ['label' => 'Cancelled', 'badge' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'hex' => '#64748B'],
            default => ['label' => ucfirst((string) $this->status), 'badge' => 'bg-light text-dark border', 'hex' => '#64748B'],
        };
    }
}
