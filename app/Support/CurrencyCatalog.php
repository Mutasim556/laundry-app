<?php

namespace App\Support;

class CurrencyCatalog
{
    public const CURRENCIES = [
        'BDT' => ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'decimals' => 2, 'locale' => 'en-BD', 'stripe' => true, 'paypal' => false],
        'GBP' => ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimals' => 2, 'locale' => 'en-GB', 'stripe' => true, 'paypal' => true],
        'USD' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'locale' => 'en-US', 'stripe' => true, 'paypal' => true],
        'EUR' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'locale' => 'en-IE', 'stripe' => true, 'paypal' => true],
        'INR' => ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimals' => 2, 'locale' => 'en-IN', 'stripe' => true, 'paypal' => false],
        'AED' => ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED', 'decimals' => 2, 'locale' => 'en-AE', 'stripe' => true, 'paypal' => false],
        'AUD' => ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2, 'locale' => 'en-AU', 'stripe' => true, 'paypal' => true],
        'CAD' => ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2, 'locale' => 'en-CA', 'stripe' => true, 'paypal' => true],
        'SGD' => ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimals' => 2, 'locale' => 'en-SG', 'stripe' => true, 'paypal' => true],
        'MYR' => ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimals' => 2, 'locale' => 'ms-MY', 'stripe' => true, 'paypal' => true],
        'SAR' => ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR', 'decimals' => 2, 'locale' => 'en-SA', 'stripe' => true, 'paypal' => false],
        'PKR' => ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => 'Rs', 'decimals' => 2, 'locale' => 'en-PK', 'stripe' => true, 'paypal' => false],
        'JPY' => ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0, 'locale' => 'ja-JP', 'stripe' => true, 'paypal' => true],
        'CHF' => ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimals' => 2, 'locale' => 'de-CH', 'stripe' => true, 'paypal' => true],
        'NZD' => ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimals' => 2, 'locale' => 'en-NZ', 'stripe' => true, 'paypal' => true],
        'QAR' => ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QAR', 'decimals' => 2, 'locale' => 'en-QA', 'stripe' => true, 'paypal' => false],
        'KWD' => ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KWD', 'decimals' => 3, 'locale' => 'en-KW', 'stripe' => false, 'paypal' => false],
        'CNY' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2, 'locale' => 'zh-CN', 'stripe' => true, 'paypal' => false],
        'HKD' => ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimals' => 2, 'locale' => 'en-HK', 'stripe' => true, 'paypal' => true],
        'SEK' => ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'decimals' => 2, 'locale' => 'sv-SE', 'stripe' => true, 'paypal' => true],
        'NOK' => ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'decimals' => 2, 'locale' => 'nb-NO', 'stripe' => true, 'paypal' => true],
        'DKK' => ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'decimals' => 2, 'locale' => 'da-DK', 'stripe' => true, 'paypal' => true],
        'PLN' => ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł', 'decimals' => 2, 'locale' => 'pl-PL', 'stripe' => true, 'paypal' => true],
        'ZAR' => ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'decimals' => 2, 'locale' => 'en-ZA', 'stripe' => true, 'paypal' => false],
        'THB' => ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'decimals' => 2, 'locale' => 'th-TH', 'stripe' => true, 'paypal' => true],
        'IDR' => ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimals' => 2, 'locale' => 'id-ID', 'stripe' => true, 'paypal' => false],
        'PHP' => ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'decimals' => 2, 'locale' => 'en-PH', 'stripe' => true, 'paypal' => true],
        'TRY' => ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺', 'decimals' => 2, 'locale' => 'tr-TR', 'stripe' => true, 'paypal' => false],
    ];

    public const COUNTRY_DEFAULT_CURRENCY = [
        'BD' => 'BDT',
        'GB' => 'GBP',
        'US' => 'USD',
        'IN' => 'INR',
        'AE' => 'AED',
        'AU' => 'AUD',
        'CA' => 'CAD',
        'SG' => 'SGD',
        'MY' => 'MYR',
        'SA' => 'SAR',
        'PK' => 'PKR',
        'JP' => 'JPY',
        'CH' => 'CHF',
        'NZ' => 'NZD',
        'QA' => 'QAR',
        'KW' => 'KWD',
        'CN' => 'CNY',
        'HK' => 'HKD',
        'SE' => 'SEK',
        'NO' => 'NOK',
        'DK' => 'DKK',
        'PL' => 'PLN',
        'ZA' => 'ZAR',
        'TH' => 'THB',
        'ID' => 'IDR',
        'PH' => 'PHP',
        'TR' => 'TRY',
        'AT' => 'EUR', 'BE' => 'EUR', 'CY' => 'EUR', 'EE' => 'EUR', 'FI' => 'EUR',
        'FR' => 'EUR', 'DE' => 'EUR', 'GR' => 'EUR', 'IE' => 'EUR', 'IT' => 'EUR',
        'LV' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'MT' => 'EUR', 'NL' => 'EUR',
        'PT' => 'EUR', 'SK' => 'EUR', 'SI' => 'EUR', 'ES' => 'EUR',
    ];

    public static function countries(): array
    {
        return array_map(static function (array $country) {
            $iso = strtoupper($country['iso']);
            $defaultCurrency = self::COUNTRY_DEFAULT_CURRENCY[$iso] ?? 'USD';

            return [
                'iso' => $iso,
                'name' => $country['name'],
                'default_currency' => $defaultCurrency,
            ];
        }, CountryDialCodes::all());
    }

    public static function currency(string $code): array
    {
        $upper = strtoupper(trim($code));

        return self::CURRENCIES[$upper] ?? self::CURRENCIES['BDT'];
    }

    public static function defaultCurrencyForCountry(string $iso): string
    {
        return self::COUNTRY_DEFAULT_CURRENCY[strtoupper(trim($iso))] ?? 'USD';
    }
}
