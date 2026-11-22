<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmailOtp extends Model
{
    public const EXPIRY_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $table = 'customer_email_otps';

    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'resend_available_at',
        'consumed_at',
        'ip_address',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }
}
