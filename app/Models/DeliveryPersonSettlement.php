<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonSettlement extends Model
{
    protected $fillable = [
        'settlement_reference',
        'delivery_person_id',
        'admin_id',
        'amount',
        'currency',
        'settlement_channel',
        'settled_at',
        'notes',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
