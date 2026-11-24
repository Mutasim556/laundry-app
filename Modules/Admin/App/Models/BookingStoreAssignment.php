<?php

namespace Modules\Admin\App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;

class BookingStoreAssignment extends Model
{
    protected $table = 'booking_store_assignments';

    protected $fillable = [
        'booking_id',
        'store_id',
        'is_current',
        'status',
        'customer_postcode',
        'store_postcode',
        'postcode_matched',
        'override_reason',
        'reassignment_reason',
        'store_name_snapshot',
        'store_code_snapshot',
        'store_address_snapshot',
        'store_city_snapshot',
        'store_postcode_snapshot',
        'store_phone_snapshot',
        'assigned_by_admin_id',
        'assigned_at',
        'superseded_by_admin_id',
        'superseded_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'postcode_matched' => 'boolean',
        'assigned_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function assignedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by_admin_id');
    }

    public function supersededByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'superseded_by_admin_id');
    }
}
