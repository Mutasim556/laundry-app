<?php

namespace Modules\Admin\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAssignment extends Model
{
    protected $table = 'delivery_assignments';

    public const TYPE_CUSTOMER_COLLECTION = 'customer_collection';
    public const TYPE_FINAL_DELIVERY = 'final_delivery';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_HANDED_OVER = 'handed_over';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ASSIGNED => 'Assigned',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_PICKED_UP => 'Picked Up',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_HANDED_OVER => 'Delivered / Handed Over',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_ASSIGNED => [self::STATUS_ACCEPTED, self::STATUS_PICKED_UP, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED => [self::STATUS_PICKED_UP, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT, self::STATUS_HANDED_OVER, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_IN_TRANSIT => [self::STATUS_HANDED_OVER, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_HANDED_OVER => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED => [self::STATUS_ASSIGNED],
        self::STATUS_CANCELLED => [],
    ];

    protected $fillable = [
        'assignment_reference',
        'booking_id',
        'delivery_person_id',
        'store_id',
        'assignment_type',
        'status',
        'pickup_location',
        'pickup_postcode',
        'destination_location',
        'destination_postcode',
        'matched_postcode',
        'coverage_override_reason',
        'scheduled_at',
        'assigned_at',
        'accepted_at',
        'picked_up_at',
        'handed_over_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'rate_minor',
        'tip_share_minor',
        'is_manual_rate_override',
        'currency',
        'rate_override_reason',
        'earnings_status',
        'cod_expected_minor',
        'cod_collected_minor',
        'cod_handover_status',
        'cod_collected_at',
        'cod_handed_over_at',
        'cod_notes',
        'proof_of_delivery_notes',
        'assigned_by_admin_id',
        'completed_by_admin_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cod_collected_at' => 'datetime',
        'cod_handed_over_at' => 'datetime',
        'rate_minor' => 'integer',
        'tip_share_minor' => 'integer',
        'is_manual_rate_override' => 'boolean',
        'cod_expected_minor' => 'integer',
        'cod_collected_minor' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class)->withTrashed();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function assignedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by_admin_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryAssignmentEvent::class)->orderBy('occurred_at');
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(\App\Models\BookingStorePickupRequest::class, 'delivery_assignment_id')->orderByDesc('id');
    }

    public function latestPickupRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\BookingStorePickupRequest::class, 'delivery_assignment_id')->latest('id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(DeliveryPersonLedgerEntry::class, 'delivery_assignment_id')->orderByDesc('id');
    }

    public function isCollectionJob(): bool
    {
        return $this->assignment_type === self::TYPE_CUSTOMER_COLLECTION;
    }

    public function isFinalDeliveryJob(): bool
    {
        return $this->assignment_type === self::TYPE_FINAL_DELIVERY;
    }

    public function getJobCodeAttribute(): string
    {
        $prefix = $this->isCollectionJob() ? 'COL' : 'DEL';

        return $prefix . '-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve independent job-level status label, key, and badge style.
     *
     * @return array{key: string, label: string, badge_style: string, pickup_request_label: ?string}
     */
    public function getJobStatusDisplay(): array
    {
        $booking = $this->booking;
        $completedStages = $booking ? $booking->getCompletedOperationalStatuses() : [];

        if ($this->isCollectionJob()) {
            $isDone = in_array($this->status, [self::STATUS_HANDED_OVER, self::STATUS_COMPLETED], true)
                || in_array(Booking::STATUS_STORE_RECEIVED, $completedStages, true);

            if ($isDone) {
                return [
                    'key' => 'completed',
                    'label' => 'Completed',
                    'badge_style' => 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;',
                    'pickup_request_label' => null,
                ];
            }

            if ($this->status === self::STATUS_PICKED_UP || in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true)) {
                return [
                    'key' => 'active',
                    'label' => 'Picked Up (En Route to Store)',
                    'badge_style' => 'background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;',
                    'pickup_request_label' => null,
                ];
            }

            return [
                'key' => 'pending',
                'label' => 'Pending Customer Collection',
                'badge_style' => 'background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;',
                'pickup_request_label' => null,
            ];
        }

        // Final Delivery Job
        $pickupReq = $this->relationLoaded('latestPickupRequest')
            ? $this->latestPickupRequest
            : $this->latestPickupRequest()->first();

        $pickupReqLabel = $pickupReq ? $pickupReq->getStatusLabel() : 'Not Sent';

        if (
            $this->status === self::STATUS_COMPLETED
            || ($booking && $booking->booking_status === Booking::STATUS_COMPLETED && in_array(Booking::STATUS_COMPLETED, $completedStages, true))
        ) {
            return [
                'key' => 'completed',
                'label' => 'Completed',
                'badge_style' => 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        if (
            ($booking && $booking->booking_status === Booking::STATUS_ON_DELIVERY)
            || ($pickupReq && $pickupReq->status === \App\Models\BookingStorePickupRequest::STATUS_COLLECTED)
            || $this->status === self::STATUS_PICKED_UP
        ) {
            return [
                'key' => 'on_delivery',
                'label' => 'On Delivery',
                'badge_style' => 'background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        if ($pickupReq && $pickupReq->status === \App\Models\BookingStorePickupRequest::STATUS_ACCEPTED) {
            return [
                'key' => 'accepted',
                'label' => 'Accepted — Ready for Store Pickup',
                'badge_style' => 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        if ($pickupReq && $pickupReq->status === \App\Models\BookingStorePickupRequest::STATUS_PENDING) {
            return [
                'key' => 'pickup_requested',
                'label' => 'Pickup Requested',
                'badge_style' => 'background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        if ($pickupReq && $pickupReq->status === \App\Models\BookingStorePickupRequest::STATUS_DECLINED) {
            return [
                'key' => 'declined',
                'label' => 'Declined',
                'badge_style' => 'background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        if (! in_array(Booking::STATUS_STORE_COMPLETE, $completedStages, true)) {
            return [
                'key' => 'awaiting_store',
                'label' => 'Awaiting Store Completion',
                'badge_style' => 'background-color: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;',
                'pickup_request_label' => $pickupReqLabel,
            ];
        }

        return [
            'key' => 'awaiting_request',
            'label' => 'Awaiting Pickup Request',
            'badge_style' => 'background-color: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE;',
            'pickup_request_label' => $pickupReqLabel,
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->assignment_type === self::TYPE_CUSTOMER_COLLECTION
            ? 'Customer Collection'
            : 'Final Delivery';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function canTransitionTo(string $target): bool
    {
        return in_array($target, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }
}
