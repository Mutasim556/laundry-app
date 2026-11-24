<?php

namespace Modules\Admin\Models;

use App\Models\DeliveryPersonBankAccount;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Models\StoreUser;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class DeliveryPerson extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'delivery_persons';

    protected $fillable = [
        'code',
        'full_name',
        'email',
        'phone_country_code',
        'phone',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_photo',
        'internal_notes',
        'address_line_1',
        'address_line_2',
        'city',
        'county_region',
        'country_code',
        'country_name',
        'residential_postcode',
        'full_residential_address',
        'employment_type',
        'work_status',
        'availability_status',
        'joining_date',
        'store_id',
        'vehicle_type',
        'vehicle_registration',
        'license_details',
        'work_notes',
        'payment_calculation_type',
        'per_collection_rate_minor',
        'per_delivery_rate_minor',
        'currency',
        'payment_effective_date',
        'payment_notes',
        'created_by_admin_id',
        'updated_by_admin_id',
        'archive_reason',
        'portal_access_enabled',
        'password',
        'phone_normalized',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'payment_effective_date' => 'date',
        'per_collection_rate_minor' => 'integer',
        'per_delivery_rate_minor' => 'integer',
        'portal_access_enabled' => 'boolean',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(DeliveryPersonCashTransaction::class)->orderByDesc('id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(DeliveryPersonSettlement::class)->orderByDesc('settled_at');
    }

    public function isEligibleToLogin(): bool
    {
        return (bool) $this->portal_access_enabled
            && $this->work_status === 'active'
            && ! $this->trashed()
            && ! empty($this->password);
    }

    public static function normalizePhone(?string $rawPhone, ?string $countryCode = null): ?string
    {
        return StoreUser::normalizePhone($rawPhone, $countryCode);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DeliveryPersonDocument::class);
    }

    public function postcodes(): HasMany
    {
        return $this->hasMany(DeliveryPersonPostcode::class)->orderBy('postcode');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryAssignmentEvent::class)->orderByDesc('occurred_at');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(DeliveryPersonLedgerEntry::class)->orderByDesc('occurred_at');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(DeliveryPersonPayout::class)->orderByDesc('payment_date');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(DeliveryPersonBankAccount::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(DeliveryPersonWithdrawalRequest::class)->orderByDesc('requested_at');
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->full_name)) ?: [];
        $first = strtoupper(substr($parts[0] ?? 'D', 0, 1));
        $second = isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '';

        return $first . $second;
    }

    public function getPhoneDisplayAttribute(): string
    {
        return trim(($this->phone_country_code ? $this->phone_country_code . ' ' : '') . $this->phone);
    }

    public function isEligibleForAssignment(): bool
    {
        return $this->work_status === 'active' && ! $this->trashed();
    }
}
