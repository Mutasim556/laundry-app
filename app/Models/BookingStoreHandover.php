<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

class BookingStoreHandover extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_reference',
        'booking_id',
        'store_id',
        'collection_delivery_person_id',
        'status',
        'requested_at',
        'accepted_at',
        'rejected_at',
        'accepted_by_store_user_id',
        'rejected_by_store_user_id',
        'rejection_reason',
        'request_note',
        'booking_version',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'booking_version' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function collectionDeliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'collection_delivery_person_id');
    }

    public function acceptedByStoreUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'accepted_by_store_user_id');
    }

    public function rejectedByStoreUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class, 'rejected_by_store_user_id');
    }
}
