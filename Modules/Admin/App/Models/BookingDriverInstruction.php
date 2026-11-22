<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingDriverInstruction extends Model
{
    protected $table = 'booking_driver_instructions';

    protected $fillable = [
        'title',
        'description',
        'type',
        'allowed_address_types',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'allowed_address_types' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->whereIn('type', [$type, 'both']);
    }

    public function supportsAddressType(?string $addressType): bool
    {
        $allowed = $this->allowed_address_types;
        if (empty($allowed) || ! is_array($allowed)) {
            return true;
        }

        $normalized = strtoupper(trim((string) ($addressType ?: 'HOME')));

        return in_array($normalized, array_map('strtoupper', $allowed), true);
    }
}
