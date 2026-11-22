<?php

use App\Models\Booking;
use App\Models\CustomerPaymentMethod;
use App\Models\User;
use App\Services\StripePaymentService;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-payments', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'refund-payments', 'guard_name' => 'admin']);

    config([
        'services.stripe.key' => 'pk_test_12345',
        'services.stripe.secret' => 'sk_test_12345',
        'services.stripe.webhook_secret' => 'whsec_test_12345',
        'services.stripe.currency' => 'gbp',
    ]);
});

function makeCompleteBookingSession(): array
{
    $unit = Unit::create(['name' => 'Kilogram', 'short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold',
        'price' => 600.00,
        'is_active' => true,
    ]);

    return [
        'booking' => [
            'services' => [[
                'category_id' => $cat->id,
                'service_id' => $svc->id,
                'option_service_id' => $svc->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold',
            ]],
            'address' => [
                'collection_mode' => 'home_office',
                'selected_address' => '10 DOWNING STREET, LONDON, SW1A 2AA',
                'postcode' => 'SW1A 2AA',
                'address_type' => 'HOME',
            ],
            'schedule' => [
                'collection_date' => now()->addDay()->format('Y-m-d'),
                'collection_slot' => '20:00 - 23:00',
                'delivery_date' => now()->addDays(2)->format('Y-m-d'),
                'delivery_slot' => '20:00 - 23:00',
            ],
            'details' => [
                'customer_type' => 'individual',
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'customer_name' => 'Test Customer',
                'customer_phone' => '+44 7700900077',
                'customer_email' => 'customer@example.com',
            ],
        ],
    ];
}

test('stripe option appears in step 5 and setup intent initializes without charging', function () {
    $session = makeCompleteBookingSession();

    $mock = Mockery::mock(StripePaymentService::class)->makePartial();
    $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn('cus_test_123');
    $mock->shouldReceive('createSetupIntent')->once()->andReturn([
        'id' => 'seti_test_123',
        'client_secret' => 'seti_test_123_secret_abc',
        'customer_id' => 'cus_test_123',
    ]);
    app()->instance(StripePaymentService::class, $mock);

    $this->withSession($session)
        ->get(route('booking.confirm'))
        ->assertOk()
        ->assertSee('Credit / Debit Card — Stripe')
        ->assertSee('Cash on Delivery');

    $setupResp = $this->withSession($session)->postJson(route('booking.stripe.setup-intent'));
    $setupResp->assertOk()
        ->assertJson([
            'success' => true,
            'setup_intent_id' => 'seti_test_123',
            'client_secret' => 'seti_test_123_secret_abc',
        ]);
});

test('confirming booking with stripe and save_card saves masked metadata and consent without charging', function () {
    $user = User::factory()->create(['email' => 'customer@example.com']);
    $session = makeCompleteBookingSession();
    $session['booking']['stripe_customer_id'] = 'cus_test_123';

    $mock = Mockery::mock(StripePaymentService::class)->makePartial();
    $mock->shouldReceive('retrievePaymentMethod')->with('pm_test_4242')->once()->andReturn([
        'id' => 'pm_test_4242',
        'customer' => 'cus_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2030,
    ]);
    app()->instance(StripePaymentService::class, $mock);

    $resp = $this->actingAs($user, 'web')
        ->withSession($session)
        ->post(route('booking.confirm.store'), [
            'payment_method' => 'stripe',
            'stripe_setup_intent_id' => 'seti_test_123',
            'stripe_payment_method_id' => 'pm_test_4242',
            'save_card' => '1',
            'stripe_consent' => '1',
        ]);

    $resp->assertRedirect(route('booking.success', session('booking.reference')));

    $booking = Booking::first();
    expect($booking)->not->toBeNull();
    expect($booking->payment_gateway)->toBe('stripe');
    expect($booking->payment_status)->toBe('authorized_for_later_charge');
    expect($booking->consent_accepted)->toBeTrue();
    expect($booking->paid_at)->toBeNull();

    $savedCard = CustomerPaymentMethod::where('user_id', $user->id)->first();
    expect($savedCard)->not->toBeNull();
    expect($savedCard->card_brand)->toBe('visa');
    expect($savedCard->card_last4)->toBe('4242');
});

test('customer cannot delete another customers saved payment method', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $pm = CustomerPaymentMethod::create([
        'user_id' => $owner->id,
        'customer_email' => $owner->email,
        'stripe_customer_id' => 'cus_owner',
        'stripe_payment_method_id' => 'pm_owner_123',
        'card_brand' => 'visa',
        'card_last4' => '4242',
        'card_exp_month' => 12,
        'card_exp_year' => 2030,
    ]);

    $this->actingAs($other, 'web')
        ->deleteJson(route('booking.stripe.saved-cards.destroy', $pm->id))
        ->assertStatus(403);
});

test('admin can finalize amount, charge off-session, handle failure, retry, and refund with permission checks', function () {
    $this->get(route('admin.payments.index'))->assertRedirect(route('admin.login'));

    $unauthorized = Admin::factory()->create();
    $this->actingAs($unauthorized, 'admin')->get(route('admin.payments.index'))->assertStatus(403);

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-payments', 'manage-payments', 'refund-payments']);

    $booking = Booking::create([
        'reference' => 'FGL-TEST01',
        'customer_name' => 'Test User',
        'customer_email' => 'test@example.com',
        'customer_phone' => '+44 7700900077',
        'address_snapshot' => ['selected_address' => '10 DOWNING ST'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [['name' => 'Wash & Fold']],
        'payment_gateway' => 'stripe',
        'payment_status' => 'authorized_for_later_charge',
        'currency' => 'GBP',
        'stripe_customer_id' => 'cus_test_1',
        'stripe_payment_method_id' => 'pm_test_1',
        'consent_accepted' => true,
        'consent_timestamp' => now(),
    ]);

    $mock = Mockery::mock(StripePaymentService::class)->makePartial();
    $mock->shouldReceive('chargeSavedPaymentMethod')
        ->once()
        ->andReturn(['status' => 'failed', 'payment_intent_id' => 'pi_fail_1', 'client_secret' => null, 'error' => 'Your card was declined.']);
    $mock->shouldReceive('chargeSavedPaymentMethod')
        ->once()
        ->andReturn(['status' => 'succeeded', 'payment_intent_id' => 'pi_ok_1', 'client_secret' => null, 'error' => null]);
    $mock->shouldReceive('refundBooking')
        ->once()
        ->andReturn(['success' => true, 'refund_id' => 're_123', 'status' => 'succeeded', 'error' => null]);
    app()->instance(StripePaymentService::class, $mock);

    // 1. Initial charge fails
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.payments.finalize', $booking->id), [
            'final_amount' => 24.50,
            'initiate_charge' => true,
        ])
        ->assertStatus(422)
        ->assertJson(['status' => 'failed']);

    expect($booking->fresh()->final_amount_minor)->toBe(2450);

    // 2. Retry succeeds
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.payments.retry', $booking->id))
        ->assertOk()
        ->assertJson(['status' => 'paid']);

    expect($booking->fresh()->payment_status)->toBe('paid');

    // 3. Refund succeeds
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.payments.refund', $booking->id))
        ->assertOk();

    expect($booking->fresh()->payment_status)->toBe('refunded');
    expect($booking->fresh()->stripe_refund_id)->toBe('re_123');
});

test('stripe webhook verifies signature, updates booking status, and ignores duplicate events', function () {
    $booking = Booking::create([
        'reference' => 'FGL-WH01',
        'customer_name' => 'Webhook User',
        'customer_email' => 'wh@example.com',
        'customer_phone' => '+44 7700900077',
        'address_snapshot' => [],
        'schedule_snapshot' => [],
        'services_snapshot' => [],
        'payment_gateway' => 'stripe',
        'payment_status' => 'payment_processing',
        'currency' => 'GBP',
        'stripe_payment_intent_id' => 'pi_wh_123',
    ]);

    $fakeEvent = \Stripe\Event::constructFrom([
        'id' => 'evt_unique_001',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_wh_123',
            ],
        ],
    ]);

    $mock = Mockery::mock(StripePaymentService::class)->makePartial();
    $mock->shouldReceive('constructWebhookEvent')->twice()->andReturn($fakeEvent);
    app()->instance(StripePaymentService::class, $mock);

    $this->postJson(route('stripe.webhook'), [])
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($booking->fresh()->payment_status)->toBe('paid');

    // Duplicate delivery is safely ignored
    $this->postJson(route('stripe.webhook'), [])
        ->assertOk()
        ->assertJson(['received' => true, 'duplicate' => true]);
});
