<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Support\CurrencyCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\App\Models\CurrencyChangeAuditLog;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Setting;

class CurrencySettingController extends Controller
{
    public function index(CurrencyService $currencyService)
    {
        $current = $currencyService->getGlobalSettings();
        $countries = CurrencyCatalog::countries();
        $currencies = CurrencyCatalog::CURRENCIES;
        $servicesCount = Schema::hasTable('services') ? Service::count() : 0;
        $auditLogs = Schema::hasTable('currency_change_audit_logs')
            ? CurrencyChangeAuditLog::orderByDesc('id')->limit(20)->get()
            : collect();

        return view('admin::currency-settings.index', compact(
            'current',
            'countries',
            'currencies',
            'servicesCount',
            'auditLogs'
        ));
    }

    public function update(Request $request, CurrencyService $currencyService): RedirectResponse
    {
        $countries = collect(CurrencyCatalog::countries())->keyBy('iso');
        $allowedCountries = $countries->keys()->all();
        $allowedCurrencies = array_keys(CurrencyCatalog::CURRENCIES);

        $current = $currencyService->getGlobalSettings();
        $fromCountry = $current['business_country'];
        $fromCurrency = $current['default_currency'];

        $validated = $request->validate([
            'business_country_code' => ['required', 'string', Rule::in($allowedCountries)],
            'default_currency' => ['required', 'string', Rule::in($allowedCurrencies)],
            'repricing_policy' => ['nullable', Rule::in(['keep_manual', 'exchange_rate'])],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'rate_source' => ['nullable', 'string', 'max:120'],
            'confirm_currency_change' => ['nullable', 'boolean'],
        ]);

        $toCountryCode = strtoupper($validated['business_country_code']);
        $toCountry = $countries->get($toCountryCode)['name'];
        $toCurrency = strtoupper($validated['default_currency']);
        $currencyMeta = CurrencyCatalog::currency($toCurrency);

        $currencyChanged = $fromCurrency !== $toCurrency;
        $policy = $validated['repricing_policy'] ?? 'keep_manual';
        $rate = isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null;

        if ($currencyChanged) {
            if (! $request->boolean('confirm_currency_change')) {
                return back()->withErrors([
                    'confirm_currency_change' => "Please confirm the currency change from {$fromCurrency} to {$toCurrency}.",
                ])->withInput();
            }

            if ($policy === 'exchange_rate' && ($rate === null || $rate <= 0)) {
                return back()->withErrors([
                    'exchange_rate' => 'Please enter a valid positive exchange rate for conversion.',
                ])->withInput();
            }
        }

        $affectedServices = 0;

        DB::transaction(function () use (
            $request,
            $currencyService,
            $fromCountry,
            $toCountry,
            $toCountryCode,
            $fromCurrency,
            $toCurrency,
            $currencyMeta,
            $currencyChanged,
            $policy,
            $rate,
            &$affectedServices
        ) {
            if ($currencyChanged && Schema::hasTable('services')) {
                $decimals = (int) $currencyMeta['decimals'];
                $services = Service::all();
                $affectedServices = $services->count();

                foreach ($services as $svc) {
                    if ($svc->original_currency === null) {
                        $svc->original_currency = $svc->currency_code ?: $fromCurrency;
                        $svc->original_amount = $svc->price;
                    }

                    if ($policy === 'exchange_rate' && $rate !== null) {
                        $converted = function_exists('bcmul')
                            ? (float) bcmul((string) $svc->price, (string) $rate, $decimals)
                            : round(((float) $svc->price) * $rate, $decimals);
                        $svc->price = $converted;
                    }

                    $svc->currency_code = $toCurrency;
                    $svc->save();
                }
            }

            if ($currencyChanged && Schema::hasTable('booking_fee_settings')) {
                $decimals = (int) $currencyMeta['decimals'];
                $fees = BookingFeeSetting::all();

                foreach ($fees as $fee) {
                    if ($fee->original_currency === null) {
                        $fee->original_currency = $fee->currency_code ?: $fromCurrency;
                        $fee->original_amount = $fee->fee_value;
                    }

                    if ($policy === 'exchange_rate' && $rate !== null) {
                        if ($fee->fee_type === 'fixed') {
                            $convertedFee = round(((float) $fee->fee_value) * $rate, $decimals);
                            $fee->fee_value = $convertedFee;
                            $fee->fee_value_minor = $currencyService->toMinor($convertedFee, $toCurrency);
                        }
                        if ($fee->threshold_amount_minor > 0) {
                            $oldThreshold = $currencyService->fromMinor((int) $fee->threshold_amount_minor, $fromCurrency);
                            $newThreshold = round($oldThreshold * $rate, $decimals);
                            $fee->threshold_amount_minor = $currencyService->toMinor($newThreshold, $toCurrency);
                        }
                    }

                    $fee->currency_code = $toCurrency;
                    $fee->save();
                }
            }

            Setting::set('business_country', $toCountry);
            Setting::set('business_country_code', $toCountryCode);
            Setting::set('default_currency', $toCurrency);
            Setting::set('currency_symbol', $currencyMeta['symbol']);
            Setting::set('currency_locale', $currencyMeta['locale']);
            Setting::set('currency_decimals', (string) $currencyMeta['decimals']);

            $admin = Auth::guard('admin')->user();
            CurrencyChangeAuditLog::create([
                'admin_id' => $admin?->id,
                'admin_name' => $admin?->name ?? 'Admin',
                'from_country' => $fromCountry,
                'to_country' => $toCountry,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'repricing_policy' => $currencyChanged ? $policy : 'no_currency_change',
                'exchange_rate' => $policy === 'exchange_rate' ? $rate : null,
                'rate_source' => $validated['rate_source'] ?? null,
                'affected_services_count' => $affectedServices,
                'summary' => $currencyChanged
                    ? "Changed currency {$fromCurrency} -> {$toCurrency} ({$policy}); historical bookings preserved."
                    : "Updated country to {$toCountry} ({$toCurrency}).",
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('admin.currency-settings.index')
            ->with('success', "Global Country & Currency updated to {$toCountry} ({$toCurrency} — {$currencyMeta['symbol']}).");
    }
}
