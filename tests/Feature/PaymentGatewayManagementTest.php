<?php

use App\Models\Booking;
use App\Services\PayPalPaymentService;
use App\Services\StripePaymentService;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Support\Facades\Http;
use Modules\Admin\App\Models\PaymentGateway;
use Modules\Admin\App\Models\PaymentGatewayAuditLog;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-payment-gateways', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-payment-gateways', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'admin']);
});

function makeGatewayAdmin(array $permissions = ['view-payment-gateways', 'manage-payment-gateways']): Admin
{
    $admin = Admin::create([
        'name' => 'Gateway Admin '.uniqid(),
        'email' => 'gwadmin+'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

function makeStep5SessionPayload(): array
{
    $unit = Unit::create(['name' => 'Kilogram', 'short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold-'.uniqid(), 'is_active' => true]);
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

it('lists only the 3 fixed gateways in admin panel and excludes bkash, nagad, bank_transfer', function () {
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.payment-gateways.index'));

    $response->assertOk();
    $response->assertSee('Credit / Debit Card');
    $response->assertSee('PayPal');
    $response->assertSee('Cash on Delivery');
    $response->assertDontSee('bKash');
    $response->assertDontSee('Nagad');
    $response->assertDontSee('Bank Transfer');
    expect(PaymentGateway::count())->toBe(3);
});

it('enforces view-payment-gateways and manage-payment-gateways permissions', function () {
    app(PaymentGatewaySeeder::class)->run();
    $viewer = makeGatewayAdmin(['view-payment-gateways']);
    $noAccess = makeGatewayAdmin(['view-payments']);

    $this->actingAs($noAccess, 'admin')
        ->get(route('admin.payment-gateways.index'))
        ->assertForbidden();

    $this->actingAs($viewer, 'admin')
        ->get(route('admin.payment-gateways.index'))
        ->assertOk();

    $this->actingAs($viewer, 'admin')
        ->post(route('admin.payment-gateways.toggle', 'cod'), ['is_active' => 0])
        ->assertForbidden();
});

it('encrypts stripe and paypal secrets in database, masks them in UI, and preserves secrets when left blank', function () {
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.stripe.update'), [
            'display_name' => 'Credit / Debit Card — Stripe',
            'description' => 'Pay securely with card.',
            'environment' => 'sandbox',
            'publishable_key' => 'pk_test_abc123',
            'secret_key' => 'sk_test_supersecret999',
            'webhook_secret' => 'whsec_supersecret888',
            'currency' => 'gbp',
            'capture_mode' => 'delayed_setup_intent',
            'enable_saved_cards' => '1',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.payment-gateways.index'));

    $stripe = PaymentGateway::where('gateway_code', 'stripe')->firstOrFail();
    expect($stripe->is_active)->toBeTrue();
    expect($stripe->encrypted_credentials)->not->toContain('sk_test_supersecret999');
    expect($stripe->getCredentials()['secret_key'])->toBe('sk_test_supersecret999');
    expect($stripe->getCredentials()['webhook_secret'])->toBe('whsec_supersecret888');

    // Update display_name without providing secret_key -> existing secret must remain intact
    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.stripe.update'), [
            'display_name' => 'Stripe Card Payment',
            'description' => 'Updated description.',
            'environment' => 'sandbox',
            'publishable_key' => 'pk_test_abc123',
            'secret_key' => '',
            'webhook_secret' => '',
            'currency' => 'GBP',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.payment-gateways.index'));

    $stripe->refresh();
    expect($stripe->display_name)->toBe('Stripe Card Payment');
    expect($stripe->getCredentials()['secret_key'])->toBe('sk_test_supersecret999');

    // Admin index must never expose raw secrets
    $page = $this->actingAs($admin, 'admin')->get(route('admin.payment-gateways.index'));
    $page->assertOk();
    $page->assertDontSee('sk_test_supersecret999');
    $page->assertDontSee('whsec_supersecret888');
    $page->assertSee('********');
});

it('prevents activating stripe or paypal when required credentials are missing', function () {
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.toggle', 'paypal'), ['is_active' => 1])
        ->assertSessionHas('error');

    expect(PaymentGateway::where('gateway_code', 'paypal')->value('is_active'))->toBeFalsy();
});

it('configures paypal, tests oauth connection, and reflects active gateways in configured sort_order on Step 5', function () {
    \Modules\Admin\Models\Setting::set('default_currency', 'GBP');
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'fake_paypal_token',
        ], 200),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.paypal.update'), [
            'display_name' => 'PayPal Express',
            'description' => 'Pay via PayPal after weighing.',
            'environment' => 'sandbox',
            'client_id' => 'paypal_client_123',
            'client_secret' => 'paypal_secret_xyz',
            'currency' => 'GBP',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.payment-gateways.index'));

    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.cod.update'), [
            'display_name' => 'Cash on Delivery',
            'description' => 'Pay on delivery.',
            'instructions' => 'Cash or POS',
            'sort_order' => 2,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.payment-gateways.index'));

    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.test', 'paypal'))
        ->assertRedirect(route('admin.payment-gateways.index'));

    $paypal = PaymentGateway::where('gateway_code', 'paypal')->firstOrFail();
    expect($paypal->last_test_status)->toBe('success');

    // Step 5 shows PayPal before Cash on Delivery and never shows bkash/nagad/bank_transfer or secrets
    $step5 = $this->withSession(['booking' => makeStep5SessionPayload()])
        ->get(route('booking.confirm'));

    $step5->assertOk();
    $step5->assertSeeInOrder(['PayPal Express', 'Cash on Delivery']);
    $step5->assertDontSee('bKash');
    $step5->assertDontSee('Nagad');
    $step5->assertDontSee('Bank Transfer');
    $step5->assertDontSee('paypal_secret_xyz');
});

it('shows unavailable message and disables confirm button when all gateways are inactive, and rejects server-side submission', function () {
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    PaymentGateway::query()->update(['is_active' => false]);

    $sessionPayload = makeStep5SessionPayload();
    $step5 = $this->withSession(['booking' => $sessionPayload])
        ->get(route('booking.confirm'));

    $step5->assertOk();
    $step5->assertSee('No payment methods are currently available. Please contact support.');
    $step5->assertSee('disabled', false);

    // Server-side rejection for deactivated or removed methods
    $this->withSession(['booking' => $sessionPayload])
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod'])
        ->assertSessionHasErrors('payment_method');

    $this->withSession(['booking' => $sessionPayload])
        ->post(route('booking.confirm.store'), ['payment_method' => 'bkash'])
        ->assertSessionHasErrors('payment_method');
});

it('removes gateway configuration without deleting historical bookings and records audit logs', function () {
    \Modules\Admin\Models\Setting::set('default_currency', 'GBP');
    $admin = makeGatewayAdmin();
    app(PaymentGatewaySeeder::class)->run();

    // Configure & activate PayPal
    $this->actingAs($admin, 'admin')
        ->post(route('admin.payment-gateways.paypal.update'), [
            'display_name' => 'PayPal',
            'description' => 'Pay via PayPal',
            'environment' => 'sandbox',
            'client_id' => 'client_id_abc',
            'client_secret' => 'client_secret_abc',
            'currency' => 'GBP',
            'sort_order' => 2,
            'is_active' => '1',
        ]);

    // Customer books with PayPal
    $customer = \App\Models\User::factory()->create();
    $this->actingAs($customer, 'web')
        ->withSession(['booking' => makeStep5SessionPayload()])
        ->post(route('booking.confirm.store'), ['payment_method' => 'paypal'])
        ->assertRedirect();

    $booking = Booking::where('payment_gateway', 'paypal')->firstOrFail();
    expect($booking->payment_status)->toBe('awaiting_final_amount');

    // Admin removes PayPal configuration
    $this->actingAs($admin, 'admin')
        ->delete(route('admin.payment-gateways.remove', 'paypal'))
        ->assertRedirect(route('admin.payment-gateways.index'));

    $paypal = PaymentGateway::where('gateway_code', 'paypal')->firstOrFail();
    expect($paypal->is_active)->toBeFalse();
    expect($paypal->encrypted_credentials)->toBeNull();
    expect($paypal->configuration_status)->toBe('unconfigured');

    // Historical booking remains intact
    expect(Booking::find($booking->id))->not->toBeNull();

    // Audit log recorded without exposing secrets
    $audit = PaymentGatewayAuditLog::where('gateway_code', 'paypal')->where('action', 'removed')->first();
    expect($audit)->not->toBeNull();
});
