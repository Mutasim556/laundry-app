<?php

use App\Services\CurrencyService;

if (! function_exists('formatMoney')) {
    function formatMoney(float|int|string $amount, ?string $currencyCode = null, bool $useSymbol = false): string
    {
        return app(CurrencyService::class)->formatMoney($amount, $currencyCode, $useSymbol);
    }
}

if (! function_exists('currencyCode')) {
    function currencyCode(): string
    {
        return app(CurrencyService::class)->code();
    }
}

if (! function_exists('currencySymbol')) {
    function currencySymbol(?string $currencyCode = null): string
    {
        return app(CurrencyService::class)->symbol($currencyCode);
    }
}
