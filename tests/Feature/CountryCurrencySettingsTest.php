<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\PayPalPaymentService;
use App\Services\StripePaymentService;
use Database\Seeders\BookingFeeSettingSeeder;
use Database\Seeders\PaymentGatewaySeeder;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\App\Models\CurrencyChangeAuditLog;
use Modules\Admin\App\Models\PaymentGateway;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'admin']);
    app(BookingFeeSettingSeeder::class)->run();
    app(PaymentGatewaySeeder::class)->run();
});

function makeCurrencyAdmin(array $permissions = ['manage-settings']): Admin
{
    $admin = Admin::create([
        'name' => 'Currency Admin '.uniqid(),
        'email' => 'curradmin+'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

function seedSampleServiceForCurrency(float $price = 600.00): array
{
    $unit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-'.uniqid(), 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold-'.uniqid(),
        'price' => $price,
        'currency_code' => 'BDT',
        'original_currency' => 'BDT',
        'original_amount' => $price,
        'is_active' => true,
    ]);

    return [$cat, $svc];
}

it('defaults to Bangladesh (BDT) and enforces manage-settings permission', function () {
    $unauthorized = makeCurrencyAdmin(['view-payments']);
    $admin = makeCurrencyAdmin(['manage-settings']);

    $this->actingAs($unauthorized, 'admin')
        ->get(route('admin.currency-settings.index'))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.currency-settings.index'))
        ->assertOk()
        ->assertSee('Bangladesh')
        ->assertSee('BDT');

    $currencyService = app(CurrencyService::class);
    expect($currencyService->code())->toBe('BDT');
    expect($currencyService->formatMoney(600))->toBe('BDT 600.00');
});

it('requires explicit confirmation before changing currency and converts prices cleanly while preserving original amounts and historical bookings', function () {
    $admin = makeCurrencyAdmin();
    [$cat, $svc] = seedSampleServiceForCurrency(1000.00);

    $serviceFee = BookingFeeSetting::where('fee_code', 'service_fee')->firstOrFail();
    $serviceFee->update(['is_active' => true, 'fee_value' => 100.00, 'fee_value_minor' => 10000]);

    // Create a confirmed historical booking in BDT BEFORE changing currency
    $customer = User::factory()->create(['profile_completed_at' => now()]);
    $historicalBooking = Booking::create([
        'reference' => 'LND-2026-000999',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+8801712345678',
        'address_snapshot' => ['selected_address' => 'Dhaka'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [['name' => 'Wash & Fold', 'pricing_type_label' => 'BDT 1,000.00 / kg']],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending_item_count',
        'booking_status' => 'confirmed',
        'is_locked' => true,
        'currency' => 'BDT',
        'estimated_amount_minor' => 110000,
        'service_fee_minor' => 10000,
        'collection_delivery_fee_minor' => 4000,
        'confirmed_at' => now(),
    ]);

    // Attempt change BDT -> GBP without confirmation checkbox -> rejected
    $this->actingAs($admin, 'admin')
        ->post(route('admin.currency-settings.update'), [
            'business_country_code' => 'GB',
            'default_currency' => 'GBP',
            'repricing_policy' => 'exchange_rate',
            'exchange_rate' => '0.0065',
        ])
        ->assertSessionHasErrors('confirm_currency_change');

    // Confirm change BDT -> GBP with approved exchange rate 0.0065
    $this->actingAs($admin, 'admin')
        ->post(route('admin.currency-settings.update'), [
            'business_country_code' => 'GB',
            'default_currency' => 'GBP',
            'repricing_policy' => 'exchange_rate',
            'exchange_rate' => '0.0065',
            'rate_source' => 'Bank Reference Rate',
            'confirm_currency_change' => '1',
        ])
        ->assertRedirect(route('admin.currency-settings.index'));

    // Global settings updated
    expect(Setting::get('business_country'))->toBe('United Kingdom');
    expect(Setting::get('default_currency'))->toBe('GBP');
    expect(app(CurrencyService::class)->code())->toBe('GBP');

    // Service price converted (1000 * 0.0065 = 6.50) with original BDT preserved
    $svc->refresh();
    expect((float) $svc->price)->toBe(6.50);
    expect($svc->currency_code)->toBe('GBP');
    expect($svc->original_currency)->toBe('BDT');
    expect((float) $svc->original_amount)->toBe(1000.00);

    // Audit log recorded
    expect(CurrencyChangeAuditLog::where('from_currency', 'BDT')->where('to_currency', 'GBP')->exists())->toBeTrue();

    // Newly rendered frontend pages display GBP
    $this->get(route('services.index', ['category' => $cat->slug]))
        ->assertOk()
        ->assertSee('GBP 6.50');

    // Stripe and PayPal read GBP
    expect(app(StripePaymentService::class)->currency())->toBe('gbp');
    expect(app(PayPalPaymentService::class)->currency())->toBe('GBP');

    // Historical confirmed booking STILL displays BDT and retains original amounts
    $historicalBooking->refresh();
    expect($historicalBooking->currency)->toBe('BDT');
    expect($historicalBooking->service_fee_minor)->toBe(10000);

    $this->actingAs($customer, 'web')
        ->get(route('orders.show', $historicalBooking->reference))
        ->assertOk()
        ->assertSee('BDT 100.00');
});

it('disables PayPal at checkout when active currency is BDT and enables it when active currency is GBP', function () {
    $paypal = PaymentGateway::where('gateway_code', 'paypal')->firstOrFail();
    $paypal->public_configuration = ['client_id' => 'cid', 'currency' => 'GBP'];
    $paypal->setCredentials(['client_secret' => 'csec']);
    $paypal->is_active = true;
    $paypal->syncConfigurationStatus();
    $paypal->save();

    // Active global currency is BDT -> PayPal unsupported & excluded
    Setting::set('default_currency', 'BDT');
    $methodsBdt = \App\Http\Controllers\BookingAddressController::getSupportedPaymentMethods();
    expect(array_key_exists('paypal', $methodsBdt))->toBeFalse();
    expect(array_key_exists('cod', $methodsBdt))->toBeTrue();

    // Switch global currency to GBP -> PayPal supported & included
    Setting::set('default_currency', 'GBP');
    $methodsGbp = \App\Http\Controllers\BookingAddressController::getSupportedPaymentMethods();
    expect(array_key_exists('paypal', $methodsGbp))->toBeTrue();
});
