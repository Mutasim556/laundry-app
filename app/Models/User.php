<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'email_verified_at',
    'country_code',
    'phone',
    'gender',
    'profile_completed_at',
    'avatar',
    'password',
    'is_blocked',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(CustomerPaymentMethod::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed_at !== null && ! empty($this->name);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }

        return $initials !== '' ? $initials : 'U';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . $this->avatar);
        }

        return asset('admin/assets/images/users/2.jpg');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }
}
