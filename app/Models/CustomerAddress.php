<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $table = 'customer_addresses';

    protected $fillable = [
        'user_id',
        'label',
        'address_type',
        'postcode',
        'formatted_address',
        'address_details',
        'hotel_name',
        'hotel_address',
        'room_number',
        'hotel_collection_type',
        'is_default',
        'contact_type',
        'contact_first_name',
        'contact_last_name',
        'contact_company_name',
        'contact_tax_number',
        'contact_country_iso',
        'contact_dialing_code',
        'contact_phone',
        'contact_email',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasContactDetails(): bool
    {
        return ! empty($this->contact_first_name) || ! empty($this->contact_phone) || ! empty($this->contact_email);
    }

    public function getContactFullNameAttribute(): string
    {
        return trim((string) $this->contact_first_name.' '.(string) $this->contact_last_name);
    }

    public function getDisplaySummaryAttribute(): string
    {
        if ($this->address_type === 'hotel') {
            $parts = array_filter([
                $this->hotel_name,
                $this->hotel_address,
                $this->room_number ? 'Room '.$this->room_number : null,
            ]);

            return implode(' — ', $parts);
        }

        return (string) ($this->formatted_address ?: $this->postcode ?: 'Saved Address');
    }
}
