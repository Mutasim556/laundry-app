<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

class BookingStorePickupRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COLLECTED = 'collected';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending Acceptance',
        self::STATUS_ACCEPTED => 'Request Accepted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COLLECTED => 'Collected From Store',
        self::STATUS_COMPLETED => 'Delivered / Completed',
    ];

    protected $fillable = [
        'request_reference',
        'booking_id',
        'store_id',
        'delivery_assignment_id',
        'delivery_person_id',
        'status',
        'requested_by_store_user_id',
        'requested_at',
        'responded_at',
        'accepted_at',
        'declined_at',
        'decline_reason',
        'cancelled_at',
        'cancellation_reason',
        'collected_at',
        'delivered_at',
        'request_note',
        'proof_of_delivery_notes',
        'booking_version',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'collected_at' => 'datetime',
            'delivered_at' => 'datetime',
            'booking_version' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id')->withTrashed();
    }

    public function requestedByStoreUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'requested_by_store_user_id');
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
