<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

class ClaimDeliveryJob extends Model
{
    public const TYPE_REWASH_COLLECTION = 'rewash_collection';
    public const TYPE_REWASH_DELIVERY = 'rewash_delivery';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_STORE_RECEIVED = 'store_received';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'job_reference',
        'claim_id',
        'booking_id',
        'store_id',
        'delivery_person_id',
        'job_type',
        'status',
        'pickup_location',
        'destination_location',
        'rate_minor',
        'currency',
        'assigned_at',
        'picked_up_at',
        'completed_at',
        'assigned_by_admin_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate_minor' => 'integer',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }
}
