<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotels';

    protected $fillable = [
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'county',
        'postcode',
        'postcode_compact',
        'country',
        'latitude',
        'longitude',
        'phone',
        'website',
        'source',
        'dedup_hash',
        'is_active',
        'is_demo',
    ];

    protected $appends = [
        'formatted_address',
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
        static::saving(function (Hotel $hotel) {
            $normalized = UkAddress::normalizePostcode((string) $hotel->postcode);
            $hotel->postcode = $normalized ?? strtoupper(trim((string) $hotel->postcode));
            $hotel->postcode_compact = UkAddress::compactPostcode((string) $hotel->postcode);
            $hotel->country = trim((string) ($hotel->country ?: 'United Kingdom'));
            $hotel->dedup_hash = self::generateDedupHash($hotel->toArray());
        });
    }

    /**
     * Generate a deterministic SHA-256 hash to prevent duplicate hotel records.
     */
    public static function generateDedupHash(array $attrs): string
    {
        $parts = [
            strtoupper(trim((string) ($attrs['name'] ?? ''))),
            UkAddress::compactPostcode((string) ($attrs['postcode'] ?? '')),
            strtoupper(trim((string) ($attrs['address_line_1'] ?? ''))),
            strtoupper(trim((string) ($attrs['city'] ?? ''))),
        ];

        return hash('sha256', implode('|', $parts));
    }

    /**
     * Scope to active hotels only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Case-insensitive partial search by name, address, city, or postcode.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        $compact = UkAddress::compactPostcode($term);

        return $query->where(function (Builder $q) use ($term, $compact) {
            $q->where('name', 'like', '%' . $term . '%')
                ->orWhere('address_line_1', 'like', '%' . $term . '%')
                ->orWhere('address_line_2', 'like', '%' . $term . '%')
                ->orWhere('city', 'like', '%' . $term . '%')
                ->orWhere('postcode', 'like', '%' . $term . '%');

            if ($compact !== '') {
                $q->orWhere('postcode_compact', 'like', '%' . $compact . '%');
            }
        });
    }

    /**
     * Calculate Haversine distance in kilometers between two lat/lon points.
     */
    public static function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Scope to active hotels that have stored coordinates.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Format the uppercase full hotel address + postcode.
     */
    public function getFormattedAddressAttribute(): string
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
