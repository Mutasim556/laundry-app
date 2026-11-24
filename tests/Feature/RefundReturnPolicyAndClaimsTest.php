<?php

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\BookingOperationalItem;
use App\Models\ClaimDeliveryJob;
use App\Models\CustomerServiceClaim;
use App\Models\RefundReturnPolicy;
use App\Models\RefundReturnPolicyVersion;
use App\Models\RefundTransaction;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-admin-accounts', 'guard_name' => 'admin']);
});

function makePolicyTestAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Policy Admin',
        'email' => 'poladmin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-bookings', 'manage-bookings', 'view-admin-accounts']);

    return $admin;
}

function makeClaimTestBooking(User $customer, Store $store, string $status = Booking::STATUS_COMPLETED, string $gateway = 'stripe'): array
{
    $cat = ItemCategory::create(['name' => 'Garments', 'slug' => 'garments-' . random_int(10, 99), 'is_active' => true]);
    $svc = Service::create(['category_id' => $cat->id, 'name' => 'Silk Wash', 'slug' => 'silk-' . random_int(10, 99), 'pricing_mode' => 'fixed', 'price_minor' => 5000, 'is_active' => true]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+44 7700900111',
        'booking_status' => $status,
        'completed_operational_stages' => [Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()]],
        'currency' => 'GBP',
        'estimated_amount_minor' => 5000,
        'final_amount_minor' => 5000,
        'payment_gateway' => $gateway,
        'payment_status' => 'paid',
        'stripe_payment_intent_id' => $gateway === 'stripe' ? 'pi_claim_001' : null,
        'assigned_store_id' => $store->id,
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => ['full_address' => '10 Downing St', 'postcode' => 'SW1A 2AA', 'country_code' => 'GB'],
        'schedule_snapshot' => [],
    ]);
    $booking->assigned_store_id = $store->id;
    $booking->save();

    $opItem = BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'category_id' => $cat->id,
        'category_name' => $cat->name,
        'service_id' => $svc->id,
        'service_name' => $svc->name,
        'pricing_mode' => 'fixed',
        'quantity' => 1,
        'catalog_unit_price_minor' => 5000,
        'actual_unit_price_minor' => 5000,
        'line_total_minor' => 5000,
        'is_removed' => false,
    ]);

    $payment = BookingCustomerPayment::create([
        'booking_id' => $booking->id,
        'payment_method' => $gateway === 'stripe' ? BookingCustomerPayment::METHOD_STRIPE : BookingCustomerPayment::METHOD_CASH_IN_HAND,
        'payment_channel' => $gateway === 'stripe' ? BookingCustomerPayment::CHANNEL_GATEWAY : BookingCustomerPayment::CHANNEL_CASH,
        'amount_minor' => 5000,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => $gateway === 'stripe' ? 'pi_claim_001' : null,
        'stripe_payment_intent_id' => $gateway === 'stripe' ? 'pi_claim_001' : null,
        'idempotency_key' => 'bcp-clm-' . random_int(1000, 9999),
        'verified_at' => now(),
    ]);

    return [$booking, $opItem, $payment];
}

test('admin policy versioning, customer claim with private evidence, stripe refund execution, and current account integration', function () {
    Storage::fake('local');
    $admin = makePolicyTestAdmin();
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();

    $store = Store::create([
        'name' => 'Kensington Laundry',
        'slug' => 'kensington-' . random_int(1000, 9999),
        'store_code' => 'KEN-' . random_int(1000, 9999),
        'email' => 'ken' . random_int(100, 999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '12 High St',
        'city' => 'London',
        'postcode' => 'W8 4PT',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    // 1. Admin creates draft policy & publishes v1
    $this->actingAs($admin, 'admin')
        ->get(route('admin.refund-returns.policies.index'))
        ->assertOk()
        ->assertSee('Policy Settings');

    $policy = RefundReturnPolicy::where('code', 'DEFAULT-GLOBAL')->firstOrFail();
    $draftV1 = $policy->versions()->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.policies.publish', $draftV1))
        ->assertRedirect();
    expect($draftV1->fresh()->status)->toBe(RefundReturnPolicyVersion::STATUS_PUBLISHED);

    // 2. Customer opens My Orders and submits a Damage claim with private photo evidence
    [$booking, $opItem, $payment] = makeClaimTestBooking($customer, $store, Booking::STATUS_COMPLETED, 'stripe');

    $this->actingAs($customer)
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('Need Help With This Order?')
        ->assertSee('Report Damaged / Missing Item');

    $evidenceFile = UploadedFile::fake()->image('torn_sleeve.jpg');
    $resSubmit = $this->actingAs($customer)
        ->postJson(route('orders.claims.store', $booking->reference), [
            'claim_type' => CustomerServiceClaim::TYPE_DAMAGE_MISSING,
            'reason_code' => 'damaged_garment',
            'description' => 'Sleeve tear noticed upon delivery.',
            'preferred_resolution' => 'partial_refund',
            'requested_amount' => 25.00,
            'affected_quantity' => 1,
            'operational_item_ids' => [$opItem->id],
            'evidence' => [$evidenceFile],
        ]);
    $resSubmit->assertOk()->assertJsonPath('success', true);

    $claim = CustomerServiceClaim::with('evidence')->where('booking_id', $booking->id)->firstOrFail();
    expect($claim->evidence)->toHaveCount(1);
    expect($claim->policy_version_id)->toBe($draftV1->id);

    // Private evidence: authorized for owner & admin, 403 for other customer
    $ev = $claim->evidence->first();
    $this->actingAs($otherCustomer)->get(route('orders.claims.evidence', $ev))->assertStatus(403);
    $this->actingAs($customer)->get(route('orders.claims.evidence', $ev))->assertOk();

    // 3. Admin cannot approve refund exceeding confirmed paid amount (GBP 50.00)
    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.cases.refunds.store', $claim), [
            'amount' => 60.00,
            'payment_method' => RefundTransaction::METHOD_STRIPE,
        ])->assertSessionHasErrors('amount');

    // Approve partial refund GBP 20.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.cases.refunds.store', $claim), [
            'amount' => 20.00,
            'payment_method' => RefundTransaction::METHOD_STRIPE,
            'booking_customer_payment_id' => $payment->id,
        ])->assertRedirect();

    $refundTx = RefundTransaction::where('claim_id', $claim->id)->firstOrFail();
    expect($refundTx->status)->toBe(RefundTransaction::STATUS_APPROVED);
    expect($booking->fresh()->refunded_amount_minor)->toBe(0); // Not yet executed!

    // 4. Mock Stripe refund API & execute refund
    $mockStripe = Mockery::mock(StripePaymentService::class);
    $mockStripe->shouldReceive('createRefund')
        ->once()
        ->with('pi_claim_001', 2000, $refundTx->idempotency_key)
        ->andReturn([
            'id' => 're_test_001',
            'status' => 'succeeded',
            'amount_minor' => 2000,
            'currency' => 'GBP',
        ]);
    $this->app->instance(StripePaymentService::class, $mockStripe);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.refunds.execute', $refundTx))
        ->assertRedirect();

    $refundTx->refresh();
    $booking->refresh();
    expect($refundTx->status)->toBe(RefundTransaction::STATUS_SUCCEEDED)
        ->and($refundTx->provider_refund_id)->toBe('re_test_001')
        ->and($booking->refunded_amount_minor)->toBe(2000)
        ->and($booking->payment_status)->toBe('partial_refund');

    // 5. Current Account shows the completed GBP 20.00 refund in Refunds Recorded & Transaction History
    $this->actingAs($admin, 'admin')
        ->get(route('admin.current-account.index', ['currency' => 'GBP', 'month' => now()->format('Y-m')]))
        ->assertOk()
        ->assertSee('GBP 20.00')
        ->assertSee($refundTx->refund_reference);
});

test('cod manual refund, rewash logistics jobs, and store liability adjustment work independently', function () {
    $admin = makePolicyTestAdmin();
    $customer = User::factory()->create();
    $store = Store::create([
        'name' => 'Chelsea Press',
        'slug' => 'chelsea-' . random_int(1000, 9999),
        'store_code' => 'CHL-' . random_int(1000, 9999),
        'email' => 'chl' . random_int(100, 999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '5 King Rd',
        'city' => 'London',
        'postcode' => 'SW3 4LY',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
    ]);
    $dp = DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => 'Rewash Driver',
        'date_of_birth' => '1990-01-01',
        'email' => 'rewdp' . random_int(100, 999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '07700799999',
        'phone_normalized' => DeliveryPerson::normalizePhone('07700799999', '+44'),
        'address_line_1' => '1 High St',
        'city' => 'London',
        'residential_postcode' => 'SW3 4LY',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026',
    ]);

    [$booking, $opItem, $payment] = makeClaimTestBooking($customer, $store, Booking::STATUS_COMPLETED, 'cod');

    // Customer submits Rewash request (no duplicate Booking created)
    $this->actingAs($customer)
        ->postJson(route('orders.claims.store', $booking->reference), [
            'claim_type' => CustomerServiceClaim::TYPE_REWASH,
            'reason_code' => 'stain_remains',
            'description' => 'Collar stain still visible.',
            'preferred_resolution' => 'free_rewash',
        ])->assertOk();

    $claim = CustomerServiceClaim::where('booking_id', $booking->id)->firstOrFail();
    expect(Booking::count())->toBe(1);

    // Admin schedules rewash collection + return jobs
    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.cases.rewash', $claim), [
            'collection_delivery_person_id' => $dp->id,
            'delivery_delivery_person_id' => $dp->id,
            'rate' => 3.50,
        ])->assertRedirect();

    expect(ClaimDeliveryJob::where('claim_id', $claim->id)->count())->toBe(2);
    expect($claim->fresh()->status)->toBe(CustomerServiceClaim::STATUS_REWASH_IN_PROGRESS);

    // Admin records store liability adjustment
    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.cases.liability', $claim), [
            'liability_party' => 'store',
            'store_liability' => 10.00,
            'reason' => 'Store missed stain pre-treatment',
        ])->assertRedirect()->assertSessionHasNoErrors();

    expect(StoreTransaction::where('store_id', $store->id)->where('transaction_type', StoreTransaction::TYPE_NEGATIVE_ADJUSTMENT)->count())->toBe(1);

    // Admin approves COD manual refund & marks Paid
    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.cases.refunds.store', $claim), [
            'amount' => 15.00,
            'payment_method' => RefundTransaction::METHOD_COD_MANUAL,
        ])->assertRedirect();

    $codRefund = RefundTransaction::where('claim_id', $claim->id)->firstOrFail();
    expect($codRefund->status)->toBe(RefundTransaction::STATUS_AWAITING_PAYOUT);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.refund-returns.refunds.cod-paid', $codRefund), [
            'payout_recipient' => $customer->name,
            'payout_method' => 'bank_transfer',
            'external_reference' => 'BACS-9911',
        ])->assertRedirect();

    expect($codRefund->fresh()->status)->toBe(RefundTransaction::STATUS_PAID)
        ->and($booking->fresh()->refunded_amount_minor)->toBe(1500);
});
