<?php

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\BookingOperationalItem;
use App\Models\User;
use App\Services\CollectionPaymentService;
use App\Services\PayPalPaymentService;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\URL;
use Modules\Admin\App\Models\PaymentGateway;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Unit;

function seedCustomerOnlinePaymentContext(string $gateway = 'stripe', bool $otpVerified = true, bool $pricingComplete = true): array
{
    $user = User::factory()->create();

    PaymentGateway::updateOrCreate(['gateway_code' => 'stripe'], [
        'display_name' => 'Stripe',
        'is_active' => true,
        'mode' => 'sandbox',
        'credentials' => [
            'publishable_key' => 'pk_test_dp_123',
            'secret_key' => 'sk_test_dp_123',
            'webhook_secret' => 'whsec_dp_123',
        ],
    ]);
    PaymentGateway::updateOrCreate(['gateway_code' => 'paypal'], [
        'display_name' => 'PayPal',
        'is_active' => true,
        'mode' => 'sandbox',
        'credentials' => [
            'client_id' => 'pp_client_123',
            'client_secret' => 'pp_secret_123',
        ],
    ]);

    $store = Store::create([
        'store_code' => 'STR-CUST-' . uniqid(),
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Test Store',
        'slug' => 'test-store-' . uniqid(),
        'contact_person' => 'Manager',
        'phone' => '07700900001',
        'email' => 'store' . uniqid() . '@example.com',
        'store_type' => 'both',
        'postcode' => 'M1 2PF',
    ]);

    $dp = DeliveryPerson::create([
        'code' => 'DP-TEST-' . rand(100, 999),
        'full_name' => 'Test Driver',
        'email' => 'driver' . uniqid() . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900' . rand(100, 999),
        'phone_normalized' => '+447700900' . rand(100, 999),
        'date_of_birth' => '1990-01-01',
        'address_line_1' => '14 Deansgate',
        'city' => 'Manchester',
        'county_region' => 'Greater Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 2PF',
        'full_residential_address' => '14 Deansgate, Manchester, M1 2PF, United Kingdom',
        'joining_date' => now()->subMonth()->toDateString(),
        'password' => 'Driver@12345',
        'work_status' => 'active',
        'portal_access_enabled' => true,
        'store_id' => $store->id,
    ]);

    $unit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-' . uniqid(), 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wf-' . uniqid(),
        'price' => 10.00,
        'is_active' => true,
    ]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . rand(100000, 999999),
        'user_id' => $user->id,
        'customer_type' => 'individual',
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'customer_phone' => '+447700900111',
        'postcode' => 'M1 2PF',
        'selected_address' => '18 London Road',
        'address_type' => 'HOME',
        'collection_date' => now()->addDay()->toDateString(),
        'collection_slot' => '10:00 - 12:00',
        'delivery_date' => now()->addDays(2)->toDateString(),
        'delivery_slot' => '14:00 - 16:00',
        'payment_gateway' => $gateway,
        'payment_status' => 'awaiting_final_amount',
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'assigned_store_id' => $store->id,
        'currency' => 'GBP',
        'estimated_amount_minor' => 2000,
        'final_amount_minor' => $pricingComplete ? 2000 : null,
        'service_fee_minor' => 0,
        'collection_delivery_fee_minor' => 0,
        'confirmed_at' => now(),
        'address_snapshot' => ['selected_address' => '18 London Road', 'postcode' => 'M1 2PF', 'address_type' => 'HOME'],
        'schedule_snapshot' => ['collection_date' => now()->addDay()->toDateString(), 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [['name' => 'Wash & Fold', 'option_name' => 'Mixed Wash & Fold']],
        'details_snapshot' => ['customer_name' => $user->name, 'customer_email' => $user->email],
        'pricing_snapshot' => ['currency' => 'GBP', 'estimated_total' => 20.00],
        'snapshot_hash' => hash('sha256', uniqid('snap', true)),
    ]);

    if ($otpVerified) {
        $booking->forceFill(['collection_otp_verified_at' => now()])->save();
    }

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'item_category_id' => $cat->id,
        'service_id' => $svc->id,
        'category_name' => 'Wash & Fold',
        'service_name' => 'Mixed Wash & Fold',
        'unit_name' => 'kg',
        'pricing_mode' => 'weight',
        'quantity' => $pricingComplete ? 2.00 : 0.00,
        'weight_kg' => $pricingComplete ? 2.00 : null,
        'catalog_unit_price_minor' => 1000,
        'actual_unit_price_minor' => 1000,
        'line_total_minor' => $pricingComplete ? 2000 : 0,
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-' . uniqid(),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'pickup_location' => '18 London Road, M1 2PF',
        'destination_location' => 'Test Store, M1 2PF',
        'status' => 'assigned',
    ]);

    return compact('user', 'store', 'dp', 'booking');
}

it('keeps Delivery Person portal read-only for online payments and blocks DP initiation routes', function () {
    $ctx = seedCustomerOnlinePaymentContext('stripe', otpVerified: false, pricingComplete: true);

    $this->actingAs($ctx['dp'], 'delivery_person')
        ->get(route('delivery-person.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Awaiting OTP Verification')
        ->assertDontSee('Generate Stripe Payment Session')
        ->assertDontSee('Generate PayPal Approval Link')
        ->assertDontSee('Pay with Stripe')
        ->assertDontSee('Confirm Physical Cash Received');

    $this->actingAs($ctx['dp'], 'delivery_person')
        ->postJson(route('delivery-person.bookings.payments.stripe', $ctx['booking']))
        ->assertStatus(403);

    $this->actingAs($ctx['dp'], 'delivery_person')
        ->postJson(route('delivery-person.bookings.payments.paypal', $ctx['booking']))
        ->assertStatus(403);
});

it('locks customer online payment before OTP verification and before pricing completion, and blocks unauthorized users', function () {
    $ctx = seedCustomerOnlinePaymentContext('stripe', otpVerified: false, pricingComplete: true);
    $otherUser = User::factory()->create();

    $this->actingAs($ctx['user'], 'web')
        ->get(route('orders.show', $ctx['booking']->reference))
        ->assertOk()
        ->assertSee('Online payment will be available after OTP verification.')
        ->assertDontSee('Pay with Stripe');

    $this->actingAs($ctx['user'], 'web')
        ->postJson(route('orders.stripe.payment-intent', $ctx['booking']->reference))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Online payment will be available after OTP verification.');

    $this->actingAs($otherUser, 'web')
        ->postJson(route('orders.stripe.payment-intent', $ctx['booking']->reference))
        ->assertStatus(403);

    $unpricedCtx = seedCustomerOnlinePaymentContext('stripe', otpVerified: true, pricingComplete: false);
    $this->actingAs($unpricedCtx['user'], 'web')
        ->get(route('orders.show', $unpricedCtx['booking']->reference))
        ->assertOk()
        ->assertSee('Awaiting Final Amount.')
        ->assertDontSee('Pay with Stripe');

    $this->actingAs($unpricedCtx['user'], 'web')
        ->postJson(route('orders.stripe.payment-intent', $unpricedCtx['booking']->reference))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Awaiting Final Amount.');
});

it('completes customer AJAX Stripe PaymentIntent flow via webhook and automatically finalizes the booking', function () {
    $ctx = seedCustomerOnlinePaymentContext('stripe', otpVerified: true, pricingComplete: true);

    $stripeMock = Mockery::mock(StripePaymentService::class)->makePartial();
    $stripeMock->shouldReceive('isActive')->andReturn(true);
    $stripeMock->shouldReceive('publishableKey')->andReturn('pk_test_dp_123');
    $stripeMock->shouldReceive('createPaymentIntent')->once()->andReturn([
        'id' => 'pi_ajax_12345',
        'client_secret' => 'pi_ajax_12345_secret_abc',
        'status' => 'requires_payment_method',
        'amount_minor' => 2000,
        'currency' => 'GBP',
    ]);
    $stripeMock->shouldReceive('constructWebhookEvent')->andReturnUsing(function (string $payload) {
        return \Stripe\Event::constructFrom(json_decode($payload, true));
    });
    app()->instance(StripePaymentService::class, $stripeMock);

    // 1. Customer sees Pay with Stripe & modal (and not PayPal)
    $this->actingAs($ctx['user'], 'web')
        ->get(route('orders.show', $ctx['booking']->reference))
        ->assertOk()
        ->assertSee('Pay with Stripe')
        ->assertSee('stripePaymentModal')
        ->assertSee('Secure Stripe Payment')
        ->assertDontSee('Pay Securely with PayPal');

    // DP sees waiting status
    $this->actingAs($ctx['dp'], 'delivery_person')
        ->get(route('delivery-person.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Awaiting Customer Online Payment');

    // 2. Customer clicks Pay Securely with Stripe -> AJAX creates PaymentIntent (no redirect)
    $this->actingAs($ctx['user'], 'web')
        ->postJson(route('orders.stripe.payment-intent', $ctx['booking']->reference))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('client_secret', 'pi_ajax_12345_secret_abc')
        ->assertJsonPath('payment_intent_id', 'pi_ajax_12345')
        ->assertJsonPath('amount_minor', 2000)
        ->assertJsonPath('currency', 'GBP');

    // Still unpaid until webhook verifies payment_intent.succeeded
    $ctx['booking']->refresh();
    expect($ctx['booking']->payment_status)->not->toBe('paid');
    expect($ctx['booking']->stripe_payment_intent_id)->toBe('pi_ajax_12345');

    // 3. Verified Stripe payment_intent.succeeded webhook marks Paid AND automatically advances through Finalized
    $webhookPayload = json_encode([
        'id' => 'evt_pi_succeeded_1',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_ajax_12345',
                'amount_received' => 2000,
                'currency' => 'gbp',
                'metadata' => ['booking_id' => (string) $ctx['booking']->id],
            ],
        ],
    ]);

    $this->postJson(route('stripe.webhook'), json_decode($webhookPayload, true))
        ->assertOk()
        ->assertJsonPath('received', true);

    $ctx['booking']->refresh();
    expect($ctx['booking']->payment_status)->toBe('paid');
    expect($ctx['booking']->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_PICKED_UP);
    expect($ctx['booking']->getCompletedOperationalStatuses())->toContain(Booking::STATUS_FINALIZED);
    expect(BookingCustomerPayment::where('booking_id', $ctx['booking']->id)->where('status', BookingCustomerPayment::STATUS_CONFIRMED)->count())->toBe(1);

    // 4. Duplicate webhook is idempotent
    $this->postJson(route('stripe.webhook'), json_decode($webhookPayload, true))
        ->assertOk()
        ->assertJsonPath('duplicate', true);
    expect(BookingCustomerPayment::where('booking_id', $ctx['booking']->id)->where('status', BookingCustomerPayment::STATUS_CONFIRMED)->count())->toBe(1);

    // 5. AJAX payment-status endpoint returns Paid & Finalized for live UI update
    $this->actingAs($ctx['user'], 'web')
        ->getJson(route('orders.payment-status', $ctx['booking']->reference))
        ->assertOk()
        ->assertJsonPath('is_payment_satisfied', true)
        ->assertJsonPath('payment_status_label', 'Paid')
        ->assertJsonPath('outstanding_minor', 0)
        ->assertJsonPath('is_finalized', true);

    // 6. Delivery Person portal reflects Booking Finalized & Paid
    $this->actingAs($ctx['dp'], 'delivery_person')
        ->get(route('delivery-person.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Booking Finalized')
        ->assertSee('Paid');
});

it('completes customer PayPal payment after OTP verification and automatically finalizes the booking', function () {
    $ctx = seedCustomerOnlinePaymentContext('paypal', otpVerified: true, pricingComplete: true);

    $ppMock = Mockery::mock(PayPalPaymentService::class)->makePartial();
    $ppMock->shouldReceive('isActive')->andReturn(true);
    $ppMock->shouldReceive('createOrder')->once()->andReturn([
        'order_id' => 'PP-CUST-999',
        'status' => 'CREATED',
        'approve_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=PP-CUST-999',
    ]);
    $ppMock->shouldReceive('captureOrder')->once()->with('PP-CUST-999')->andReturn([
        'order_id' => 'PP-CUST-999',
        'capture_id' => 'PP-CAP-888',
        'status' => 'COMPLETED',
        'amount_value' => '20.00',
        'currency_code' => 'GBP',
    ]);
    app()->instance(PayPalPaymentService::class, $ppMock);

    $this->actingAs($ctx['user'], 'web')
        ->get(route('orders.show', $ctx['booking']->reference))
        ->assertOk()
        ->assertSee('Pay Securely with PayPal')
        ->assertDontSee('Pay with Stripe');

    $this->actingAs($ctx['user'], 'web')
        ->post(route('orders.pay.paypal', $ctx['booking']->reference))
        ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PP-CUST-999');

    $ctx['booking']->refresh();
    expect($ctx['booking']->payment_status)->not->toBe('paid');

    $this->actingAs($ctx['user'], 'web')
        ->get(route('booking.paypal.capture', ['booking' => $ctx['booking']->id, 'token' => 'PP-CUST-999']))
        ->assertRedirect(route('orders.show', $ctx['booking']->reference));

    $ctx['booking']->refresh();
    expect($ctx['booking']->payment_status)->toBe('paid');
    expect($ctx['booking']->paypal_capture_id)->toBe('PP-CAP-888');
    expect($ctx['booking']->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_PICKED_UP);
    expect($ctx['booking']->getCompletedOperationalStatuses())->toContain(Booking::STATUS_FINALIZED);

    // Duplicate capture is idempotent
    $this->actingAs($ctx['user'], 'web')
        ->get(route('booking.paypal.capture', ['booking' => $ctx['booking']->id, 'token' => 'PP-CUST-999']))
        ->assertRedirect(route('orders.show', $ctx['booking']->reference));
});

it('supports partial payment without premature finalization and rejects wrong currency', function () {
    $ctx = seedCustomerOnlinePaymentContext('stripe', otpVerified: true, pricingComplete: true);
    $svc = app(CollectionPaymentService::class);

    expect(fn () => $svc->recordVerifiedGatewayPayment(
        $ctx['booking'],
        BookingCustomerPayment::METHOD_STRIPE,
        'cs_wrong_curr',
        1000,
        'USD'
    ))->toThrow(RuntimeException::class);

    $svc->recordVerifiedGatewayPayment(
        $ctx['booking'],
        BookingCustomerPayment::METHOD_STRIPE,
        'cs_partial_1',
        1000,
        'GBP',
        'Partial Stripe payment'
    );

    $ctx['booking']->refresh();
    expect($ctx['booking']->payment_status)->toBe('partially_paid');
    expect($ctx['booking']->getCompletedOperationalStatuses())->not->toContain(Booking::STATUS_FINALIZED);
    expect($svc->getPaymentState($ctx['booking'])['outstanding_minor'])->toBe(1000);
});

it('displays consistent finalized pricing and payment state across Collection DP, Final DP, and Store portals', function () {
    $ctx = seedCustomerOnlinePaymentContext('stripe', otpVerified: true, pricingComplete: true);

    // Collection DP sees Final Booking Amount + Pricing Status: Finalized
    $this->actingAs($ctx['dp'], 'delivery_person')
        ->get(route('delivery-person.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Final Booking Amount:')
        ->assertSee('Pricing Status:')
        ->assertSee('Finalized')
        ->assertSee('GBP 20.00');

    // Final DP view also displays Final Booking Amount + Pricing Status: Finalized
    $finalDp = DeliveryPerson::create([
        'code' => 'DP-FIN-' . rand(100, 999),
        'full_name' => 'Final Driver',
        'email' => 'fdriver' . uniqid() . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700901' . rand(100, 999),
        'phone_normalized' => '+447700901' . rand(100, 999),
        'date_of_birth' => '1990-01-01',
        'address_line_1' => '14 Deansgate',
        'city' => 'Manchester',
        'county_region' => 'Greater Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 2PF',
        'full_residential_address' => '14 Deansgate, Manchester, M1 2PF, United Kingdom',
        'joining_date' => now()->subMonth()->toDateString(),
        'password' => 'Driver@12345',
        'work_status' => 'active',
        'portal_access_enabled' => true,
        'store_id' => $ctx['store']->id,
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-FIN-' . uniqid(),
        'booking_id' => $ctx['booking']->id,
        'delivery_person_id' => $finalDp->id,
        'store_id' => $ctx['store']->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'pickup_location' => 'Test Store, M1 2PF',
        'destination_location' => '18 London Road, M1 2PF',
        'status' => 'assigned',
    ]);

    $this->actingAs($finalDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Booking Pricing &amp; Payment Summary', false)
        ->assertSee('Final Booking Amount')
        ->assertSee('Finalized')
        ->assertSee('GBP 20.00');

    // Store Portal also displays Finalized and Final Amount even when commission_rate is null
    \Modules\Admin\App\Models\BookingStoreAssignment::create([
        'booking_id' => $ctx['booking']->id,
        'store_id' => $ctx['store']->id,
        'store_name_snapshot' => $ctx['store']->name,
        'store_address_snapshot' => '10 High Street, M1 2PF',
        'store_postcode_snapshot' => 'M1 2PF',
        'assigned_at' => now(),
        'is_current' => true,
    ]);
    $storeUser = \App\Models\StoreUser::create([
        'store_id' => $ctx['store']->id,
        'name' => 'Store Owner',
        'email' => 'sowner' . uniqid() . '@example.com',
        'password' => 'Store@12345',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser, 'store')
        ->get(route('store.bookings.show', $ctx['booking']))
        ->assertOk()
        ->assertSee('Final Amount: GBP 20.00')
        ->assertSee('Finalized');
});
