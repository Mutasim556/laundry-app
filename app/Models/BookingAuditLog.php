<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAuditLog extends Model
{
    protected $table = 'booking_audit_logs';

    protected $fillable = [
        'booking_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'action',
        'summary',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
