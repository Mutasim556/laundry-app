<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ownership_type',
        'vendor_id',
        'admin_id',
        'status',
        'name',
        'bn_name',
        'slug',
        'logo',
        'cover_image',
        'description',
        'contact_person',
        'phone',
        'alt_phone',
        'email',
        'store_type',
        'holding_no',
        'road_no',
        'nearby_landmark',
        'full_address',
        'opening_hours',
        'division_id',
        'district_id',
        'upazila_id',
        'local_area_id',
        'area_name',
        'postcode',
        'latitude',
        'longitude',
        'service_ids',
        'pricing_model',
        'min_order_amount',
        'has_delivery',
        'delivery_fee',
        'free_delivery_threshold',
        'turnaround_time',
        'is_accepting_orders',
        'service_area_ids',
        'service_areas_text',
        'verification_notes',
        'commission_type',
        'commission_rate',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'service_ids' => 'array',
        'service_area_ids' => 'array',
        'min_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'free_delivery_threshold' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'has_delivery' => 'boolean',
        'is_accepting_orders' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
        'cover_image_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

    protected static function booted(): void
    {
        static::deleting(function (Store $store) {
            if ($store->logo && Storage::disk('public')->exists($store->logo)) {
                Storage::disk('public')->delete($store->logo);
            }
            if ($store->cover_image && Storage::disk('public')->exists($store->cover_image)) {
                Storage::disk('public')->delete($store->cover_image);
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }

    public function localArea(): BelongsTo
    {
        return $this->belongsTo(LocalArea::class);
    }
}
