<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\UkAddress;

class InternationalAddressHelper
{
    /**
     * Find country metadata by ISO2 code from CountryDialCodes.
     */
    public static function findCountry(?string $iso): ?array
    {
        if (! $iso) {
            return null;
        }

        $upper = strtoupper(trim($iso));
        foreach (CountryDialCodes::all() as $country) {
            if (strtoupper($country['iso']) === $upper) {
                return $country;
            }
        }

        return null;
    }

    /**
     * Return country-specific field labels and placeholders for the UI.
     */
    public static function addressConfigMap(): array
    {
        return [
            'GB' => [
                'state_label' => 'County / Region',
                'state_placeholder' => 'e.g. Greater London',
                'postal_label' => 'Postcode',
                'postal_placeholder' => 'Search and select UK postcode',
                'postal_required' => true,
            ],
            'US' => [
                'state_label' => 'State',
                'state_placeholder' => 'e.g. New York',
                'postal_label' => 'ZIP Code',
                'postal_placeholder' => 'e.g. 10001',
                'postal_required' => true,
            ],
            'CA' => [
                'state_label' => 'Province',
                'state_placeholder' => 'e.g. Ontario',
                'postal_label' => 'Postal Code',
                'postal_placeholder' => 'e.g. M5V 2T6',
                'postal_required' => true,
            ],
            'AU' => [
                'state_label' => 'State / Territory',
                'state_placeholder' => 'e.g. New South Wales',
                'postal_label' => 'Postcode',
                'postal_placeholder' => 'e.g. 2000',
                'postal_required' => true,
            ],
            'BD' => [
                'state_label' => 'Division / District',
                'state_placeholder' => 'e.g. Dhaka',
                'postal_label' => 'Postal Code',
                'postal_placeholder' => 'e.g. 1205',
                'postal_required' => false,
            ],
            'DEFAULT' => [
                'state_label' => 'State / Province / Region',
                'state_placeholder' => 'Enter state, province, or region',
                'postal_label' => 'Postcode / ZIP Code',
                'postal_placeholder' => 'Enter postal or ZIP code',
                'postal_required' => false,
            ],
        ];
    }

    /**
     * Validate an international phone number (6-15 digits after stripping formatting).
     *
     * @throws ValidationException
     */
    public static function validatePhone(?string $phone, string $field = 'phone'): ?string
    {
        $trimmed = trim((string) ($phone ?? ''));
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if (strlen($digits) < 6 || strlen($digits) > 15) {
            throw ValidationException::withMessages([
                $field => ['Please enter a valid phone number between 6 and 15 digits.'],
            ]);
        }

        return $trimmed;
    }

    /**
     * Validate and normalize postcode/ZIP according to the selected country.
     *
     * @throws ValidationException
     */
    public static function resolveCountryAwarePostcode(
        ?string $rawPostcode,
        ?string $countryCode,
        ?string $existingPostcode = null,
        bool $requireForUk = false
    ): ?string {
        $trimmed = trim((string) ($rawPostcode ?? ''));
        $iso = $countryCode ? strtoupper(trim($countryCode)) : null;

        // United Kingdom (or legacy call without country_code): validate against uk_addresses
        if ($iso === 'GB' || ($iso === null && $trimmed !== '')) {
            if ($trimmed === '') {
                if ($iso === 'GB' && $requireForUk) {
                    throw ValidationException::withMessages([
                        'postcode' => ['Please select a valid postcode from the UK address database.'],
                    ]);
                }

                return null;
            }

            $compact = UkAddress::compactPostcode($trimmed);
            $match = UkAddress::query()
                ->where('is_active', true)
                ->where('postcode_compact', $compact)
                ->first();

            if ($match) {
                return UkAddress::normalizePostcode($match->postcode)
                    ?? strtoupper((string) preg_replace('/\s+/', ' ', trim((string) $match->postcode)));
            }

            if ($existingPostcode !== null && trim($existingPostcode) !== '') {
                $existingCompact = UkAddress::compactPostcode($existingPostcode);
                if ($compact !== '' && $compact === $existingCompact) {
                    return $existingPostcode;
                }
            }

            throw ValidationException::withMessages([
                'postcode' => ['Please select a valid postcode from the UK address database.'],
            ]);
        }

        if ($trimmed === '') {
            return null;
        }

        if ($iso === 'US' && ! preg_match('/^\d{5}(-\d{4})?$/', $trimmed)) {
            throw ValidationException::withMessages([
                'postcode' => ['Please enter a valid US ZIP code (e.g. 10001 or 10001-1234).'],
            ]);
        }

        if ($iso === 'CA' && ! preg_match('/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/', $trimmed)) {
            throw ValidationException::withMessages([
                'postcode' => ['Please enter a valid Canadian postal code (e.g. M5V 2T6).'],
            ]);
        }

        if ($iso === 'AU' && ! preg_match('/^\d{4}$/', $trimmed)) {
            throw ValidationException::withMessages([
                'postcode' => ['Please enter a valid 4-digit Australian postcode (e.g. 2000).'],
            ]);
        }

        return strtoupper((string) preg_replace('/\s+/', ' ', $trimmed));
    }

    /**
     * Compose a clean single-line address string from structured international components.
     */
    public static function composeFullAddress(
        ?string $line1,
        ?string $line2,
        ?string $city,
        ?string $stateRegion,
        ?string $postcode,
        ?string $countryName
    ): string {
        $parts = array_filter([
            trim((string) $line1),
            trim((string) $line2),
            trim((string) $city),
            trim((string) $stateRegion),
            trim((string) $postcode),
            trim((string) $countryName),
        ], fn (string $part) => $part !== '');

        return implode(', ', $parts);
    }
}
