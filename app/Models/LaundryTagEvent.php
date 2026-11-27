<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

class LaundryTagEvent extends Model
{
    public const EVENT_GENERATED = 'generated';
    public const EVENT_PRINT_PREPARED = 'print_prepared';
    public const EVENT_PRINT_REQUESTED = 'print_requested';
    public const EVENT_PRINT_CONFIRMED = 'print_confirmed';
    public const EVENT_ALLOCATED_TO_DP = 'allocated_to_delivery_person';
    public const EVENT_ASSIGNED_TO_BOOKING = 'assigned_to_booking';
    public const EVENT_UNASSIGNED_FROM_BOOKING = 'unassigned_from_booking';
    public const EVENT_REPLACED_ON_BOOKING = 'replaced_on_booking';
    public const EVENT_HANDED_OVER_REQUESTED = 'handover_requested';
    public const EVENT_STORE_VERIFIED = 'store_verified';
    public const EVENT_TAG_PROCESSING_COMPLETED = 'tag_processing_completed';
    public const EVENT_MANIFEST_COMPLETED = 'tag_manifest_completed';
    public const EVENT_AUTO_STORE_COMPLETE = 'auto_store_complete_triggered';
    public const EVENT_MARKED_AT_STORE = 'marked_at_store';
    public const EVENT_IN_FINAL_DELIVERY = 'in_final_delivery';
    public const EVENT_RETIRED = 'retired';
    public const EVENT_RETURNED_TO_STOCK = 'returned_to_stock';
    public const EVENT_REPORTED_LOST = 'reported_lost';
    public const EVENT_REPORTED_DAMAGED = 'reported_damaged';
    public const EVENT_VOIDED = 'voided';
    public const EVENT_HANDOVER_VERIFICATION_ISSUE = 'verification_issue';

    protected $fillable = [
        'tag_id',
        'event_type',
        'previous_status',
        'new_status',
        'booking_id',
        'delivery_person_id',
        'store_id',
        'allocation_id',
        'print_job_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(LaundryTagAllocation::class, 'allocation_id');
    }

    public function printJob(): BelongsTo
    {
        return $this->belongsTo(LaundryTagPrintJob::class, 'print_job_id');
    }
}
