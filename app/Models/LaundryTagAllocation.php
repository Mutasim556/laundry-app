<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class LaundryTagAllocation extends Model
{
    protected $fillable = [
        'allocation_reference',
        'delivery_person_id',
        'quantity',
        'status',
        'notes',
        'allocated_by_admin_id',
        'allocated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'allocated_at' => 'datetime',
        ];
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id');
    }

    public function allocatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'allocated_by_admin_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            LaundryTag::class,
            'laundry_tag_allocation_items',
            'allocation_id',
            'tag_id'
        )->withTimestamps()->orderBy('laundry_tags.id');
    }
}
