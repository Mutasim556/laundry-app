<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class LaundryTag extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_AT_STORE = 'at_store';
    public const STATUS_IN_FINAL_DELIVERY = 'in_final_delivery';
    public const STATUS_RETIRED = 'retired';
    public const STATUS_LOST = 'lost';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_VOIDED = 'voided';

    public const STATUS_LABELS = [
        self::STATUS_AVAILABLE => 'Available (Admin Stock)',
        self::STATUS_ALLOCATED => 'Allocated to Delivery Person',
        self::STATUS_ASSIGNED => 'Assigned to Booking',
        self::STATUS_AT_STORE => 'At Store',
        self::STATUS_IN_FINAL_DELIVERY => 'In Final Delivery',
        self::STATUS_RETIRED => 'Retired / Completed',
        self::STATUS_LOST => 'Lost',
        self::STATUS_DAMAGED => 'Damaged',
        self::STATUS_VOIDED => 'Voided',
    ];

    public const PRINT_STATUS_UNPRINTED = 'unprinted';
    public const PRINT_STATUS_PREPARED = 'prepared';
    public const PRINT_STATUS_REQUESTED = 'requested';
    public const PRINT_STATUS_PRINTED_CONFIRMED = 'printed_confirmed';

    public const EXCEPTION_STATUSES = [
        self::STATUS_LOST,
        self::STATUS_DAMAGED,
        self::STATUS_VOIDED,
        self::STATUS_RETIRED,
    ];

    public const ACTIVE_BOOKING_STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_AT_STORE,
        self::STATUS_IN_FINAL_DELIVERY,
    ];

    protected $fillable = [
        'tag_code',
        'qr_signature',
        'batch_id',
        'status',
        'current_delivery_person_id',
        'current_booking_id',
        'print_status',
        'print_count',
        'last_printed_at',
        'created_by_admin_id',
        'allocated_at',
        'assigned_at',
        'at_store_at',
        'retired_at',
        'lost_or_damaged_at',
        'status_reason',
        'is_reusable',
    ];

    protected function casts(): array
    {
        return [
            'print_count' => 'integer',
            'last_printed_at' => 'datetime',
            'allocated_at' => 'datetime',
            'assigned_at' => 'datetime',
            'at_store_at' => 'datetime',
            'retired_at' => 'datetime',
            'lost_or_damaged_at' => 'datetime',
            'is_reusable' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LaundryTagBatch::class, 'batch_id');
    }

    public function currentDeliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'current_delivery_person_id');
    }

    public function currentBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'current_booking_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function allocations(): BelongsToMany
    {
        return $this->belongsToMany(
            LaundryTagAllocation::class,
            'laundry_tag_allocation_items',
            'tag_id',
            'allocation_id'
        )->withTimestamps();
    }

    public function bookingAssignments(): HasMany
    {
        return $this->hasMany(LaundryTagBookingAssignment::class, 'tag_id')->orderByDesc('id');
    }

    public function activeBookingAssignment(): HasOne
    {
        return $this->hasOne(LaundryTagBookingAssignment::class, 'tag_id')
            ->where('active_lock', 1)
            ->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LaundryTagEvent::class, 'tag_id')->orderByDesc('occurred_at')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getQrPayloadAttribute(): string
    {
        return $this->tag_code . '|' . $this->qr_signature;
    }

    public function isExceptionStatus(): bool
    {
        return in_array($this->status, self::EXCEPTION_STATUSES, true);
    }

    public function isActivelyAttached(): bool
    {
        return in_array($this->status, self::ACTIVE_BOOKING_STATUSES, true) || $this->current_booking_id !== null;
    }
}
