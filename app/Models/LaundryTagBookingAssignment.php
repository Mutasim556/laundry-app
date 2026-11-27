<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

class LaundryTagBookingAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_STORE_VERIFIED = 'store_verified';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_UNASSIGNED = 'unassigned';
    public const STATUS_REPLACED = 'replaced';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tag_id',
        'booking_id',
        'delivery_assignment_id',
        'handover_id',
        'delivery_person_id',
        'store_id',
        'package_label',
        'bag_sequence',
        'active_lock',
        'is_manifest_finalized',
        'status',
        'assigned_method',
        'assigned_at',
        'assigned_by_dp_id',
        'store_verified_at',
        'verified_by_store_user_id',
        'verification_method',
        'verification_notes',
        'store_processing_completed_at',
        'store_processing_completed_by_store_user_id',
        'store_processing_notes',
        'released_at',
        'release_reason',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'bag_sequence' => 'integer',
            'active_lock' => 'integer',
            'is_manifest_finalized' => 'boolean',
            'assigned_at' => 'datetime',
            'store_verified_at' => 'datetime',
            'store_processing_completed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(LaundryTag::class, 'tag_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(BookingStoreHandover::class, 'handover_id');
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function assignedByDeliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'assigned_by_dp_id');
    }

    public function verifiedByStoreUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'verified_by_store_user_id');
    }

    public function storeProcessingCompletedBy(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'store_processing_completed_by_store_user_id');
    }

    public function isVerifiedByStore(): bool
    {
        return $this->store_verified_at !== null || $this->status === self::STATUS_STORE_VERIFIED;
    }

    public function isStoreProcessingCompleted(): bool
    {
        return $this->store_processing_completed_at !== null;
    }
}
