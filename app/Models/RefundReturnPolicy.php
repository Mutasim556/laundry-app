<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Store;

class RefundReturnPolicy extends Model
{
    protected $fillable = [
        'code',
        'name',
        'country_code',
        'store_id',
        'category_id',
        'is_active',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RefundReturnPolicyVersion::class, 'policy_id')->orderByDesc('version_number');
    }

    public function currentPublishedVersion(): HasOne
    {
        return $this->hasOne(RefundReturnPolicyVersion::class, 'policy_id')
            ->where('status', RefundReturnPolicyVersion::STATUS_PUBLISHED)
            ->orderByDesc('version_number');
    }
}
