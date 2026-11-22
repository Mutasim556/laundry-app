<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryPerson extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'payment_effective_date' => 'date',
        'per_collection_rate_minor' => 'integer',
        'per_delivery_rate_minor' => 'integer',
    ];

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
