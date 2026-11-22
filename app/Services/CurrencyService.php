<?php

namespace App\Services;

use App\Support\CurrencyCatalog;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Models\Setting;

class CurrencyService
{
    public function getGlobalSettings(): array
    {
        $country = 'Bangladesh';
        $countryCode = 'BD';
        $currencyCode = 'BDT';

        try {
            if (Schema::hasTable('settings')) {
                $country = (string) (Setting::get('business_country', 'Bangladesh') ?: 'Bangladesh');
                $countryCode = strtoupper((string) (Setting::get('business_country_code', 'BD') ?: 'BD'));
                $currencyCode = strtoupper((string) (Setting::get('default_currency', 'BDT') ?: 'BDT'));
            }
        } catch (\Throwable) {
            // Safe defaults when settings table is not yet available
        }

        $meta = CurrencyCatalog::currency($currencyCode);

        return [
            'business_country' => $country,
            'business_country_code' => $countryCode,
            'default_currency' => $meta['code'],
            'currency_name' => $meta['name'],
            'currency_symbol' => $meta['symbol'],
            'currency_locale' => $meta['locale'],
            'currency_decimals' => $meta['decimals'],
            'stripe_supported' => (bool) ($meta['stripe'] ?? false),
            'paypal_supported' => (bool) ($meta['paypal'] ?? false),
        ];
    }

    public function code(): string
    {
        return $this->getGlobalSettings()['default_currency'];
    }

    public function symbol(?string $currencyCode = null): string
    {
        if ($currencyCode !== null) {
            return CurrencyCatalog::currency($currencyCode)['symbol'];
        }

        return $this->getGlobalSettings()['currency_symbol'];
    }

    public function decimals(?string $currencyCode = null): int
    {
        if ($currencyCode !== null) {
            return (int) CurrencyCatalog::currency($currencyCode)['decimals'];
        }

        return (int) $this->getGlobalSettings()['currency_decimals'];
    }

    public function toMinor(float|int|string $amount, ?string $currencyCode = null): int
    {
        $decimals = $this->decimals($currencyCode);
        $factor = 10 ** $decimals;

        return (int) round(((float) $amount) * $factor);
    }

    public function fromMinor(int $minor, ?string $currencyCode = null): float
    {
        $decimals = $this->decimals($currencyCode);
        $factor = 10 ** $decimals;

        return round($minor / $factor, $decimals);
    }

    public function formatMoney(float|int|string $amount, ?string $currencyCode = null, bool $useSymbol = false): string
    {
        $code = strtoupper($currencyCode ?: $this->code());
        $meta = CurrencyCatalog::currency($code);
        $decimals = (int) $meta['decimals'];
        $prefix = $useSymbol ? $meta['symbol'] : $meta['code'];

        return $prefix.' '.number_format((float) $amount, $decimals, '.', ',');
    }

    public function formatMinor(int $minor, ?string $currencyCode = null, bool $useSymbol = false): string
    {
        $code = strtoupper($currencyCode ?: $this->code());

        return $this->formatMoney($this->fromMinor($minor, $code), $code, $useSymbol);
    }

    public function getPublicConfig(): array
    {
        $s = $this->getGlobalSettings();

        return [
            'code' => $s['default_currency'],
            'symbol' => $s['currency_symbol'],
            'locale' => $s['currency_locale'],
            'decimals' => $s['currency_decimals'],
        ];
    }

    public function isStripeSupported(?string $currencyCode = null): bool
    {
        $code = strtoupper($currencyCode ?: $this->code());

        return (bool) (CurrencyCatalog::currency($code)['stripe'] ?? false);
    }

    public function isPayPalSupported(?string $currencyCode = null): bool
    {
        $code = strtoupper($currencyCode ?: $this->code());

        return (bool) (CurrencyCatalog::currency($code)['paypal'] ?? false);
    }
}
