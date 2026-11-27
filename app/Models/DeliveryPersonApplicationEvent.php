<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonApplicationEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'event_type',
        'actor_type',
        'actor_id',
        'notes',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonApplication::class, 'application_id');
    }
}
