<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;

class CustomerServiceClaim extends Model
{
    public const TYPE_CANCELLATION_REFUND = 'cancellation_refund';
    public const TYPE_REWASH = 'rewash';
    public const TYPE_DAMAGE_MISSING = 'damage_missing';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_AWAITING_EVIDENCE = 'awaiting_evidence';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AWAITING_REFUND_PROCESSING = 'awaiting_refund_processing';
    public const STATUS_REFUND_PROCESSING = 'refund_processing';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_REWASH_IN_PROGRESS = 'rewash_in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const ALLOWED_TRANSITIONS = [
        self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW, self::STATUS_AWAITING_EVIDENCE, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CLOSED],
        self::STATUS_UNDER_REVIEW => [self::STATUS_AWAITING_EVIDENCE, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CLOSED],
        self::STATUS_AWAITING_EVIDENCE => [self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CLOSED],
        self::STATUS_APPROVED => [self::STATUS_AWAITING_REFUND_PROCESSING, self::STATUS_REFUND_PROCESSING, self::STATUS_REFUNDED, self::STATUS_REWASH_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_AWAITING_REFUND_PROCESSING => [self::STATUS_REFUND_PROCESSING, self::STATUS_REFUNDED, self::STATUS_RESOLVED],
        self::STATUS_REFUND_PROCESSING => [self::STATUS_REFUNDED, self::STATUS_RESOLVED],
        self::STATUS_REFUNDED => [self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_REWASH_IN_PROGRESS => [self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_REJECTED => [self::STATUS_CLOSED],
        self::STATUS_RESOLVED => [self::STATUS_CLOSED],
        self::STATUS_CLOSED => [],
    ];

    protected $fillable = [
        'case_reference',
        'booking_id',
        'user_id',
        'store_id',
        'policy_version_id',
        'claim_type',
        'reason_code',
        'description',
        'preferred_resolution',
        'status',
        'requested_amount_minor',
        'approved_amount_minor',
        'currency',
        'assigned_admin_id',
        'reviewed_by_admin_id',
        'reviewed_at',
        'decision_notes',
        'liability_party',
        'store_liability_minor',
        'dp_liability_minor',
        'rewash_status',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount_minor' => 'integer',
            'approved_amount_minor' => 'integer',
            'store_liability_minor' => 'integer',
            'dp_liability_minor' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(RefundReturnPolicyVersion::class, 'policy_version_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerServiceClaimItem::class, 'claim_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ClaimEvidence::class, 'claim_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ClaimStatusHistory::class, 'claim_id')->orderByDesc('id');
    }

    public function refundTransactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class, 'claim_id')->orderByDesc('id');
    }

    public function deliveryJobs(): HasMany
    {
        return $this->hasMany(ClaimDeliveryJob::class, 'claim_id')->orderBy('id');
    }

    public function canTransitionTo(string $target): bool
    {
        return in_array($target, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->claim_type) {
            self::TYPE_CANCELLATION_REFUND => 'Cancellation & Refund',
            self::TYPE_REWASH => 'Rewash / Service Correction',
            self::TYPE_DAMAGE_MISSING => 'Damage / Missing / Wrong Item',
            default => ucfirst(str_replace('_', ' ', (string) $this->claim_type)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
