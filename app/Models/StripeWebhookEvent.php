<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'payload_summary',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_summary' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
