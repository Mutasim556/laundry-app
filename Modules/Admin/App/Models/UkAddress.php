<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UkAddress extends Model
{
    use HasFactory;

    /**
     * Standard UK Postcode regex pattern.
     */
    public const UK_POSTCODE_REGEX = '/^([A-Z]{1,2}\d[A-Z\d]?|GIR)\s*(\d[A-Z]{2})$/i';

    protected $table = 'uk_addresses';

    protected $fillable = [
        'postcode',
        'postcode_compact',
        'address_line_1',
        'address_line_2',
        'building_name',
        'building_number',
        'street',
        'city',
        'county',
        'country',
        'latitude',
        'longitude',
        'external_reference',
        'source',
        'dedup_hash',
        'is_active',
        'is_demo',
    ];

    protected $appends = [
        'full_address',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UkAddress $address) {
            $normalized = self::normalizePostcode((string) $address->postcode);
            if ($normalized !== null) {
                $address->postcode = $normalized;
            } else {
                $address->postcode = strtoupper(trim((string) $address->postcode));
            }

            $address->postcode_compact = self::compactPostcode((string) $address->postcode);
            $address->country = trim((string) ($address->country ?: 'United Kingdom'));
            $address->dedup_hash = self::generateDedupHash($address->toArray());
        });
    }

    /**
     * Normalize a UK postcode into standard "OUTWARD INWARD" uppercase format.
     */
    public static function normalizePostcode(string $raw): ?string
    {
        $clean = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', trim($raw)));
        if (preg_match('/^([A-Z]{1,2}\d[A-Z\d]?|GIR)(\d[A-Z]{2})$/', $clean, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        return null;
    }

    /**
     * Return compact uppercase postcode without spaces (e.g. E10BJ).
     */
    public static function compactPostcode(string $raw): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', trim($raw)));
    }

    /**
     * Generate a deterministic SHA-256 hash to prevent duplicate address records.
     */
    public static function generateDedupHash(array $attrs): string
    {
        $parts = [
            self::compactPostcode((string) ($attrs['postcode'] ?? '')),
            strtoupper(trim((string) ($attrs['address_line_1'] ?? ''))),
            strtoupper(trim((string) ($attrs['address_line_2'] ?? ''))),
            strtoupper(trim((string) ($attrs['building_name'] ?? ''))),
            strtoupper(trim((string) ($attrs['building_number'] ?? ''))),
            strtoupper(trim((string) ($attrs['street'] ?? ''))),
            strtoupper(trim((string) ($attrs['city'] ?? ''))),
        ];

        return hash('sha256', implode('|', $parts));
    }

    /**
     * Scope to active addresses only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to match a normalized or compact UK postcode.
     */
    public function scopeForPostcode(Builder $query, string $postcode): Builder
    {
        $compact = self::compactPostcode($postcode);

        return $query->where('postcode_compact', $compact);
    }

    /**
     * Format the full uppercase UK address string for customer display.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->county,
            $this->postcode,
        ], fn ($val) => trim((string) $val) !== '');

        return strtoupper(implode(', ', array_unique(array_map('trim', $parts))));
    }
}
