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
        'name',
        'owner_name',
        'phone',
        'alt_phone',
        'email',
        'business_address',
        'trade_license_number',
        'trade_license_document',
        'status',
        'verification_notes',
        'commission_type',
        'commission_rate',
        'payout_method',
        'payout_details',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Vendor $vendor) {
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
