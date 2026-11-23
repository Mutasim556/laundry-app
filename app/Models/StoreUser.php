<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Admin\Models\Store;

class StoreUser extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasFactory, Notifiable;

    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone_country_code',
        'phone',
        'phone_normalized',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'created_by_admin_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function isEligibleToLogin(): bool
    {
        return $this->is_active
            && $this->store !== null
            && $this->store->status === 'active'
            && ! $this->store->trashed();
    }

    public function getPhoneDisplayAttribute(): string
    {
        return trim(($this->phone_country_code ? $this->phone_country_code . ' ' : '') . ($this->phone ?? ''));
    }

    /**
     * Normalize an international phone number into a canonical "+<digits>" string.
     */
    public static function normalizePhone(?string $rawPhone, ?string $countryCode = null): ?string
    {
        $raw = trim((string) $rawPhone);
        if ($raw === '') {
            return null;
        }

        // Convert leading "00" international prefix to "+"
        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        if (! $hasPlus && $countryCode) {
            $ccDigits = preg_replace('/\D+/', '', $countryCode) ?? '';
            if ($ccDigits !== '' && ! str_starts_with($digits, $ccDigits)) {
                $digits = $ccDigits . ltrim($digits, '0');
            }
        }

        return '+' . $digits;
    }
}
