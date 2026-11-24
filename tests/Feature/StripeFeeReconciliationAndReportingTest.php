<?php

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\User;
use App\Services\StripeFeeReconciliationService;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-admin-accounts', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'adjust-admin-accounts', 'guard_name' => 'admin']);
});

test('stripe payment balance transaction fees reconcile idempotently and display on current account without double counting', function () {
    $admin = Admin::create([
        'name' => 'Stripe Finance Admin',
        'email' => 'stripefin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-admin-accounts', 'adjust-admin-accounts']);

    $customer = User::factory()->create();
    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+44 7700900111',
        'booking_status' => Booking::STATUS_FINALIZED,
        'completed_operational_stages' => [Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()]],
        'currency' => 'GBP',
        'estimated_amount_minor' => 5000,
        'final_amount_minor' => 5000,
        'payment_gateway' => 'stripe',
        'payment_status' => 'paid',
        'stripe_payment_intent_id' => 'pi_live_fee_001',
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => ['postcode' => 'E1 0BJ'],
        'schedule_snapshot' => [],
    ]);

    $paymentReconciled = BookingCustomerPayment::create([
        'booking_id' => $booking->id,
        'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 5000,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => 'pi_live_fee_001',
        'idempotency_key' => 'bcp-stripe-fee-001',
        'verified_at' => now(),
    ]);

    $paymentPending = BookingCustomerPayment::create([
        'booking_id' => $booking->id,
        'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 2500,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => 'pi_pending_fee_002',
        'idempotency_key' => 'bcp-stripe-fee-002',
        'fee_reconciliation_status' => 'pending',
        'verified_at' => now(),
    ]);

    // Mock StripePaymentService so we don't call live Stripe API in tests
    $mockStripe = Mockery::mock(StripePaymentService::class);
    $mockStripe->shouldReceive('isConfigured')->andReturn(true);
    $mockStripe->shouldReceive('retrieveBalanceTransactionForPaymentReference')
        ->once()
        ->with('pi_live_fee_001')
        ->andReturn([
            'payment_intent_id' => 'pi_live_fee_001',
            'charge_id' => 'ch_live_fee_001',
            'balance_transaction_id' => 'txn_live_fee_001',
            'amount_minor' => 5000,
            'fee_minor' => 175,
            'net_minor' => 4825,
            'currency' => 'GBP',
            'fee_details' => [
                ['type' => 'stripe_fee', 'description' => 'Stripe processing fees', 'amount' => 175, 'currency' => 'GBP'],
            ],
        ]);

    $reconciler = new StripeFeeReconciliationService($mockStripe);
    $updated = $reconciler->reconcilePayment($paymentReconciled);

    expect($updated->fee_reconciliation_status)->toBe('reconciled')
        ->and($updated->stripe_charge_id)->toBe('ch_live_fee_001')
        ->and($updated->stripe_balance_transaction_id)->toBe('txn_live_fee_001')
        ->and($updated->gateway_fee_minor)->toBe(175)
        ->and($updated->gateway_net_minor)->toBe(4825);

    // Second call is idempotent (does NOT call Stripe API again)
    $reconciler->reconcilePayment($updated);

    // Dashboard shows Gross GBP 75.00 (50 + 25), Fee -GBP 1.75, Net GBP 48.25, and Fee Pending Reconciliation for pi_pending_fee_002
    $this->actingAs($admin, 'admin')
        ->get(route('admin.current-account.index', ['currency' => 'GBP', 'month' => now()->format('Y-m')]))
        ->assertOk()
        ->assertSee('Stripe Payment Summary')
        ->assertSee('GBP 75.00')
        ->assertSee('GBP 1.75')
        ->assertSee('GBP 48.25')
        ->assertSee('Fee Pending Reconciliation');

    // Detail modal endpoint includes Stripe Charge, Balance Transaction, Fee Breakdown, and Net
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.current-account.detail', 'bcp:' . $updated->id))
        ->assertOk()
        ->assertJsonPath('transaction.detail_payload.stripe_payment_intent_id', 'pi_live_fee_001')
        ->assertJsonPath('transaction.detail_payload.stripe_charge_id', 'ch_live_fee_001')
        ->assertJsonPath('transaction.detail_payload.stripe_balance_transaction_id', 'txn_live_fee_001')
        ->assertJsonPath('transaction.detail_payload.actual_stripe_processing_fee', 'GBP 1.75')
        ->assertJsonPath('transaction.detail_payload.net_stripe_amount', 'GBP 48.25')
        ->assertJsonPath('transaction.detail_payload.fee_reconciliation_status', 'Reconciled');

    // Detail modal for unreconciled confirmed payment shows Fee Not Yet Available
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.current-account.detail', 'bcp:' . $paymentPending->id))
        ->assertOk()
        ->assertJsonPath('transaction.detail_payload.actual_stripe_processing_fee', 'Fee Not Yet Available');
});
