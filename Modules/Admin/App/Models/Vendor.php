<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_code',
        'name',
        'owner_name',
        'business_type',
        'phone_country_code',
        'phone',
        'alt_phone_country_code',
        'alt_phone',
        'email',
        'country_code',
        'country_name',
        'address_line_1',
        'address_line_2',
        'city',
        'state_region',
        'postcode',
        'business_address',
        'trade_license_number',
        'tax_vat_number',
        'description',
        'trade_license_document',
        'status',
        'verification_notes',
        'commission_type',
        'commission_rate',
        'payment_terms',
        'service_ids',
        'payout_method',
        'payout_details',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'service_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Vendor $vendor) {
            if (! $vendor->isForceDeleting()) {
                return;
            }
            if ($vendor->trade_license_document && Storage::disk('public')->exists($vendor->trade_license_document)) {
                Storage::disk('public')->delete($vendor->trade_license_document);
            }
        });
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}
