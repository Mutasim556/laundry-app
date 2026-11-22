<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingPricingService;
use Database\Seeders\BookingFeeSettingSeeder;
use Database\Seeders\PaymentGatewaySeeder;
use Modules\Admin\App\Models\BookingFeeAuditLog;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\App\Models\PaymentGateway;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-booking-fees', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-booking-fees', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'admin']);
});

function makeFeeAdmin(array $permissions = ['view-booking-fees', 'manage-booking-fees']): Admin
{
    $admin = Admin::create([
        'name' => 'Fee Admin '.uniqid(),
        'email' => 'feeadmin+'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

function makeBookingSessionForFees(): array
{
    $unit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-'.uniqid(), 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold-'.uniqid(),
        'price' => 600.00,
        'is_active' => true,
    ]);

    return [
        'services' => [
            [
                'category_id' => $cat->id,
                'service_id' => $svc->id,
                'option_service_id' => $svc->id,
                'name' => $cat->name,
                'option_name' => $svc->name,
            ],
        ],
        'address' => [
            'collection_mode' => 'home_office',
            'address_type' => 'HOME',
            'postcode' => 'E1 1AA',
            'selected_address' => '10 DOWNING STREET, LONDON, E1 1AA',
        ],
        'schedule' => [
            'collection_date' => now()->addDay()->format('Y-m-d'),
            'collection_date_label' => 'Tomorrow',
            'collection_slot' => '09:00 - 12:00',
            'collection_instruction' => 'Ring doorbell',
            'delivery_date' => now()->addDays(2)->format('Y-m-d'),
            'delivery_date_label' => 'In 2 days',
            'delivery_slot' => '18:00 - 21:00',
            'delivery_instruction' => 'Leave at door',
        ],
        'details' => [
            'customer_type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '+44 7700900077',
        ],
    ];
}

it('enforces view-booking-fees and manage-booking-fees permissions', function () {
    app(BookingFeeSettingSeeder::class)->run();
    $viewer = makeFeeAdmin(['view-booking-fees']);
    $noAccess = makeFeeAdmin(['view-payments']);

    $this->actingAs($noAccess, 'admin')
        ->get(route('admin.booking-fees.index'))
        ->assertForbidden();

    $this->actingAs($viewer, 'admin')
        ->get(route('admin.booking-fees.index'))
        ->assertOk();

    $this->actingAs($viewer, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => 50,
        ])
        ->assertForbidden();
});

it('validates negative fees and percentage limits', function () {
    $admin = makeFeeAdmin();
    app(BookingFeeSettingSeeder::class)->run();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => -10,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('fee_value');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'percentage',
            'fee_value' => 150,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('fee_value');
});

it('updates service fee and collection & delivery fee dynamically on Step 5 and records audit logs', function () {
    $admin = makeFeeAdmin();
    app(BookingFeeSettingSeeder::class)->run();
    app(PaymentGatewaySeeder::class)->run();

    // Set Service Fee to BDT 50.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => '50.00',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.booking-fees.index'));

    // Set Collection & Delivery Fee to BDT 80.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.delivery-fee.update'), [
            'display_name' => 'Collection & Delivery',
            'fee_value' => '80.00',
            'is_active' => '1',
            'is_free' => '0',
            'threshold_enabled' => '0',
            'threshold_amount' => '0',
        ])
        ->assertRedirect(route('admin.booking-fees.index'));

    $session = makeBookingSessionForFees();
    $step5 = $this->withSession(['booking' => $session])->get(route('booking.confirm'));
    $step5->assertOk();
    $step5->assertSee('BDT 50.00');
    $step5->assertSee('BDT 80.00');

    // Disable Service Fee & enable Free Collection & Delivery
    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => '50.00',
            'is_active' => '0',
        ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.delivery-fee.update'), [
            'display_name' => 'Collection & Delivery',
            'fee_value' => '80.00',
            'is_active' => '1',
            'is_free' => '1',
        ]);

    $step5Free = $this->withSession(['booking' => $session])->get(route('booking.confirm'));
    $step5Free->assertOk();
    $step5Free->assertSee('BDT 0.00');
    $step5Free->assertSee('FREE');

    expect(BookingFeeAuditLog::where('fee_code', 'service_fee')->count())->toBe(2);
    expect(BookingFeeAuditLog::where('fee_code', 'collection_delivery_fee')->count())->toBe(2);
});

it('does not grant threshold-based free delivery prematurely on unknown estimates, and grants it when confirmed amount qualifies', function () {
    $admin = makeFeeAdmin();
    app(BookingFeeSettingSeeder::class)->run();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.delivery-fee.update'), [
            'display_name' => 'Collection & Delivery',
            'fee_value' => '80.00',
            'is_active' => '1',
            'is_free' => '0',
            'threshold_enabled' => '1',
            'threshold_amount' => '1000.00',
            'threshold_after_discount' => '1',
        ]);

    $session = makeBookingSessionForFees();
    $pricingService = app(BookingPricingService::class);
    $catalog = collect();

    // Unknown final amount -> threshold pending, BDT 80.00 charged
    $unknownPricing = $pricingService->calculate($session, $catalog, null);
    expect($unknownPricing['threshold_status'])->toBe('pending');
    expect($unknownPricing['is_free_delivery'])->toBeFalse();
    expect($unknownPricing['collection_delivery_fee_minor'])->toBe(8000);

    // Confirmed amount BDT 1,200.00 (>= 1,000.00) -> free delivery qualified
    $confirmedPricing = $pricingService->calculate($session, $catalog, 120000);
    expect($confirmedPricing['threshold_status'])->toBe('qualified');
    expect($confirmedPricing['is_free_delivery'])->toBeTrue();
    expect($confirmedPricing['collection_delivery_fee_minor'])->toBe(0);
});

it('preserves fee snapshot on confirmed historical bookings when admin updates fees and applies consistent fees across COD and PayPal', function () {
    \Modules\Admin\Models\Setting::set('default_currency', 'GBP');
    $admin = makeFeeAdmin();
    app(BookingFeeSettingSeeder::class)->run();
    app(PaymentGatewaySeeder::class)->run();

    // Activate PayPal for testing consistent fee persistence
    $paypal = PaymentGateway::where('gateway_code', 'paypal')->firstOrFail();
    $paypal->public_configuration = ['client_id' => 'cid', 'currency' => 'GBP'];
    $paypal->setCredentials(['client_secret' => 'csec']);
    $paypal->is_active = true;
    $paypal->syncConfigurationStatus();
    $paypal->save();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => '50.00',
            'is_active' => '1',
        ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.delivery-fee.update'), [
            'display_name' => 'Collection & Delivery',
            'fee_value' => '80.00',
            'is_active' => '1',
            'is_free' => '0',
            'threshold_enabled' => '0',
        ]);

    $customer = User::factory()->create();
    $session = makeBookingSessionForFees();

    $this->actingAs($customer, 'web')
        ->withSession(['booking' => $session])
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod'])
        ->assertRedirect();

    $codBooking = Booking::where('payment_gateway', 'cod')->firstOrFail();
    expect($codBooking->service_fee_minor)->toBe(5000);
    expect($codBooking->collection_delivery_fee_minor)->toBe(8000);

    // PayPal booking gets identical server-side fees
    $this->actingAs($customer, 'web')
        ->withSession(['booking' => makeBookingSessionForFees()])
        ->post(route('booking.confirm.store'), ['payment_method' => 'paypal'])
        ->assertRedirect();

    $paypalBooking = Booking::where('payment_gateway', 'paypal')->firstOrFail();
    expect($paypalBooking->service_fee_minor)->toBe(5000);
    expect($paypalBooking->collection_delivery_fee_minor)->toBe(8000);

    // Admin changes fees later
    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-fees.service-fee.update'), [
            'display_name' => 'Service Fee',
            'fee_type' => 'fixed',
            'fee_value' => '120.00',
            'is_active' => '1',
        ]);

    // Historical confirmed bookings keep original fees
    $codBooking->refresh();
    expect($codBooking->service_fee_minor)->toBe(5000);
    expect($codBooking->collection_delivery_fee_minor)->toBe(8000);

    // Viewing confirmed booking on Step 5 also preserves its agreed fee snapshot
    $pricingService = app(BookingPricingService::class);
    $catalog = collect();
    $preservedPricing = $pricingService->calculate(['reference' => $codBooking->reference, 'services' => $session['services']], $catalog);
    expect($preservedPricing['service_fee_minor'])->toBe(5000);
    expect($preservedPricing['collection_delivery_fee_minor'])->toBe(8000);
});
