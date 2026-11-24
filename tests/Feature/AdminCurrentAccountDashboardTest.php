<?php

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreEarning;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-admin-accounts', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'adjust-admin-accounts', 'guard_name' => 'admin']);
});

test('admin current account aggregates verified receipts, receivables, commissions, payables, custody cash, and payouts without double counting', function () {
    $admin = Admin::create([
        'name' => 'Finance Admin',
        'email' => 'finadmin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-admin-accounts', 'adjust-admin-accounts']);

    $unauthAdmin = Admin::create([
        'name' => 'No Perm Admin',
        'email' => 'noperm' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);

    $store = Store::create([
        'name' => 'Bloomsbury Cleaners',
        'slug' => 'bloomsbury-' . random_int(1000, 9999),
        'store_code' => 'BLM-' . random_int(1000, 9999),
        'email' => 'blm' . random_int(100, 999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '10 Russell Sq',
        'city' => 'London',
        'postcode' => 'WC1B 5EH',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $dp = DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => 'Sam Rider',
        'date_of_birth' => '1990-01-01',
        'email' => 'sam' . random_int(100, 999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '07700712345',
        'phone_normalized' => DeliveryPerson::normalizePhone('07700712345', '+44'),
        'address_line_1' => '5 Fleet St',
        'city' => 'London',
        'residential_postcode' => 'EC4Y 1AA',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026',
    ]);

    $customer = User::factory()->create();
    $cat = ItemCategory::create(['name' => 'Laundry', 'slug' => 'laundry-' . random_int(10, 99), 'is_active' => true]);
    $svc = Service::create(['category_id' => $cat->id, 'name' => 'Wash', 'slug' => 'wash-' . random_int(10, 99), 'pricing_mode' => 'fixed', 'price_minor' => 5000, 'is_active' => true]);

    $makeBooking = function (string $gateway, string $payStatus, int $totalMinor, string $currency = 'GBP') use ($customer, $store, $cat, $svc) {
        $b = Booking::create([
            'reference' => 'LND-2026-' . random_int(100000, 999999),
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '+44 7700900111',
            'booking_status' => Booking::STATUS_COMPLETED,
            'completed_operational_stages' => [Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()]],
            'currency' => $currency,
            'estimated_amount_minor' => $totalMinor,
            'final_amount_minor' => $totalMinor,
            'collection_delivery_fee_minor' => 1000,
            'payment_gateway' => $gateway,
            'payment_status' => $payStatus,
            'assigned_store_id' => $store->id,
            'services_snapshot' => [],
            'contact_snapshot' => [],
            'fee_snapshot' => [],
            'address_snapshot' => ['postcode' => 'WC1B 5EH'],
            'schedule_snapshot' => [],
        ]);
        BookingOperationalItem::create([
            'booking_id' => $b->id,
            'category_id' => $cat->id,
            'category_name' => $cat->name,
            'service_id' => $svc->id,
            'service_name' => $svc->name,
            'pricing_mode' => 'fixed',
            'quantity' => 1,
            'catalog_unit_price_minor' => $totalMinor,
            'actual_unit_price_minor' => $totalMinor,
            'line_total_minor' => $totalMinor,
            'is_removed' => false,
        ]);
        BookingStoreAssignment::create([
            'booking_id' => $b->id,
            'store_id' => $store->id,
            'store_name_snapshot' => $store->name,
            'store_address_snapshot' => $store->address_line_1,
            'is_current' => true,
            'assigned_at' => now(),
        ]);

        return $b;
    };

    // 1. Stripe confirmed (GBP 50.00) + pending attempt (GBP 20.00 - ignored)
    $bStripe = $makeBooking('stripe', 'paid', 5000);
    $pStripe = BookingCustomerPayment::create([
        'booking_id' => $bStripe->id,
        'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 5000,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => 'pi_test_12345',
        'idempotency_key' => 'bcp-stripe-1',
        'verified_at' => now(),
    ]);
    BookingCustomerPayment::create([
        'booking_id' => $bStripe->id,
        'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 2000,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_PENDING,
        'external_reference' => 'pi_pending_999',
        'idempotency_key' => 'bcp-stripe-pending-1',
    ]);

    // 2. PayPal confirmed (GBP 30.00)
    $bPaypal = $makeBooking('paypal', 'paid', 3000);
    BookingCustomerPayment::create([
        'booking_id' => $bPaypal->id,
        'payment_method' => BookingCustomerPayment::METHOD_PAYPAL,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 3000,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => 'PAYID-TEST-001',
        'idempotency_key' => 'bcp-paypal-1',
        'verified_at' => now(),
    ]);

    // 3. Unpaid finalized booking (GBP 40.00 receivable)
    $makeBooking('cod', 'pending', 4000);

    // 4. COD cash collected by DP (GBP 25.00), GBP 10.00 remitted -> GBP 15.00 still held
    $bCod = $makeBooking('cod', 'paid', 2500);
    BookingCustomerPayment::create([
        'booking_id' => $bCod->id,
        'delivery_person_id' => $dp->id,
        'payment_method' => BookingCustomerPayment::METHOD_CASH_IN_HAND,
        'payment_channel' => BookingCustomerPayment::CHANNEL_CASH,
        'amount_minor' => 2500,
        'currency' => 'GBP',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'idempotency_key' => 'bcp-cod-1',
        'collected_at' => now(),
    ]);
    DeliveryPersonCashTransaction::create([
        'delivery_person_id' => $dp->id,
        'booking_id' => $bCod->id,
        'transaction_type' => DeliveryPersonCashTransaction::TYPE_CUSTOMER_CASH_COLLECTED,
        'direction' => DeliveryPersonCashTransaction::DIRECTION_CREDIT,
        'amount' => '25.00',
        'currency' => 'GBP',
        'balance_after' => '25.00',
        'payment_channel' => DeliveryPersonCashTransaction::CHANNEL_CASH_IN_HAND,
        'reference' => 'DPC-001',
        'idempotency_key' => 'dpc-001',
        'description' => 'Collected COD',
    ]);
    $settlement = DeliveryPersonSettlement::create([
        'settlement_reference' => 'STL-001',
        'delivery_person_id' => $dp->id,
        'admin_id' => $admin->id,
        'amount' => '10.00',
        'currency' => 'GBP',
        'settlement_channel' => 'cash',
        'settled_at' => now(),
        'idempotency_key' => 'stl-001',
    ]);
    DeliveryPersonCashTransaction::create([
        'delivery_person_id' => $dp->id,
        'settlement_id' => $settlement->id,
        'transaction_type' => DeliveryPersonCashTransaction::TYPE_CASH_HANDOVER_TO_ADMIN,
        'direction' => DeliveryPersonCashTransaction::DIRECTION_DEBIT,
        'amount' => '10.00',
        'currency' => 'GBP',
        'balance_after' => '15.00',
        'payment_channel' => DeliveryPersonCashTransaction::CHANNEL_ADMIN_SETTLEMENT,
        'reference' => 'DPC-002',
        'idempotency_key' => 'dpc-002',
        'description' => 'Handed over to admin',
    ]);

    // 5. Store commission (GBP 10.00) & store share (GBP 40.00), GBP 15.00 paid -> GBP 25.00 payable
    BookingStoreEarning::create([
        'booking_id' => $bStripe->id,
        'store_id' => $store->id,
        'currency' => 'GBP',
        'eligible_service_amount_minor' => 5000,
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
        'commission_amount_minor' => 1000,
        'net_store_earnings_minor' => 4000,
        'is_current' => true,
        'snapshot_hash' => hash('sha256', 'test-bse-1'),
        'finalized_at' => now(),
    ]);
    StoreAccount::create([
        'store_id' => $store->id,
        'currency' => 'GBP',
        'current_balance' => '25.00',
        'total_credited' => '40.00',
        'total_debited' => '15.00',
    ]);
    StoreTransaction::create([
        'store_id' => $store->id,
        'transaction_type' => StoreTransaction::TYPE_PAYOUT,
        'direction' => StoreTransaction::DIRECTION_DEBIT,
        'amount' => '15.00',
        'currency' => 'GBP',
        'balance_after' => '25.00',
        'reference' => 'ST-PAY-001',
        'idempotency_key' => 'st-pay-001',
        'description' => 'Store payout',
    ]);

    // 6. DP Collection (GBP 5.00) + Final Delivery (GBP 7.00) earned, GBP 4.00 paid -> GBP 8.00 payable
    $colAsg = DeliveryAssignment::create([
        'assignment_reference' => 'ASG-COL-1',
        'booking_id' => $bStripe->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => 'Customer',
        'destination_location' => 'Store',
        'rate_minor' => 500,
        'currency' => 'GBP',
        'earnings_status' => 'credited',
    ]);
    $finAsg = DeliveryAssignment::create([
        'assignment_reference' => 'ASG-FIN-1',
        'booking_id' => $bStripe->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => 'Store',
        'destination_location' => 'Customer',
        'rate_minor' => 700,
        'currency' => 'GBP',
        'earnings_status' => 'credited',
    ]);
    DeliveryPersonLedgerEntry::create([
        'entry_reference' => 'LED-COL-1',
        'delivery_person_id' => $dp->id,
        'delivery_assignment_id' => $colAsg->id,
        'booking_id' => $bStripe->id,
        'transaction_type' => DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING,
        'direction' => 'credit',
        'amount_minor' => 500,
        'currency' => 'GBP',
        'occurred_at' => now(),
    ]);
    DeliveryPersonLedgerEntry::create([
        'entry_reference' => 'LED-FIN-1',
        'delivery_person_id' => $dp->id,
        'delivery_assignment_id' => $finAsg->id,
        'booking_id' => $bStripe->id,
        'transaction_type' => DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING,
        'direction' => 'credit',
        'amount_minor' => 700,
        'currency' => 'GBP',
        'occurred_at' => now(),
    ]);
    $payoutLedger = DeliveryPersonLedgerEntry::create([
        'entry_reference' => 'LED-PAY-1',
        'delivery_person_id' => $dp->id,
        'transaction_type' => DeliveryPersonLedgerEntry::TYPE_PAYOUT,
        'direction' => 'debit',
        'amount_minor' => 400,
        'currency' => 'GBP',
        'occurred_at' => now(),
    ]);
    DeliveryPersonPayout::create([
        'payout_reference' => 'DPP-001',
        'delivery_person_id' => $dp->id,
        'ledger_entry_id' => $payoutLedger->id,
        'amount_minor' => 400,
        'currency' => 'GBP',
        'payment_method' => 'bank_transfer',
        'payment_date' => now()->toDateString(),
        'status' => 'recorded',
        'idempotency_key' => 'dpp-001',
    ]);

    // 7. USD booking should NOT mix into GBP summary
    $bUsd = $makeBooking('stripe', 'paid', 9900, 'USD');
    BookingCustomerPayment::create([
        'booking_id' => $bUsd->id,
        'payment_method' => BookingCustomerPayment::METHOD_STRIPE,
        'payment_channel' => BookingCustomerPayment::CHANNEL_GATEWAY,
        'amount_minor' => 9900,
        'currency' => 'USD',
        'status' => BookingCustomerPayment::STATUS_CONFIRMED,
        'external_reference' => 'pi_usd_only',
        'idempotency_key' => 'bcp-usd-1',
        'verified_at' => now(),
    ]);

    // Unauthorized admin blocked
    $this->actingAs($unauthAdmin, 'admin')
        ->get(route('admin.current-account.index'))
        ->assertStatus(403);

    // Authorized admin sees accurate GBP figures:
    // Platform Receipts = 50.00 (Stripe) + 30.00 (PayPal) + 10.00 (Settled DP cash) = GBP 90.00
    // Customer Receivables = GBP 40.00
    // Store Payables = GBP 25.00
    // DP Payables = GBP 8.00 (12.00 - 4.00)
    // Cash Held by DPs = GBP 15.00
    // Recorded Disbursements = 15.00 (Store payout) + 4.00 (DP payout) = GBP 19.00
    // Net Recorded Cash Movement = 90.00 - 19.00 = GBP 71.00
    $this->actingAs($admin, 'admin')
        ->get(route('admin.current-account.index', ['currency' => 'GBP']))
        ->assertOk()
        ->assertSee('GBP 90.00')
        ->assertSee('GBP 40.00')
        ->assertSee('GBP 25.00')
        ->assertSee('GBP 8.00')
        ->assertSee('GBP 15.00')
        ->assertSee('GBP 19.00')
        ->assertSee('GBP 71.00')
        ->assertSee('Customer Stripe Payment')
        ->assertSee('Customer PayPal Payment')
        ->assertSee('Store Commission Earned')
        ->assertSee('Collection Job Earning')
        ->assertSee('Final Delivery Job Earning')
        ->assertDontSee('pi_pending_999')
        ->assertDontSee('pi_usd_only');

    // AJAX transactions endpoint filters accurately and excludes pending Stripe attempts
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.current-account.transactions', [
            'currency' => 'GBP',
            'month' => now()->format('Y-m'),
            'payment_method' => 'stripe',
            'sort_by' => 'amount',
            'sort_dir' => 'desc',
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('total', 1);

    // AJAX Transaction Detail modal endpoint returns safe payload
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.current-account.detail', 'bcp:' . $pStripe->id))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('transaction.reference', 'pi_test_12345')
        ->assertJsonPath('transaction.classification', 'Cash Receipt');

    // Previous empty month shows 0.00 monthly receipts and empty state, while CSV export works
    $prevMonth = now()->subMonth()->format('Y-m');
    $prevLabel = now()->subMonth()->format('F Y');
    $this->actingAs($admin, 'admin')
        ->get(route('admin.current-account.index', ['currency' => 'GBP', 'month' => $prevMonth]))
        ->assertOk()
        ->assertSee($prevLabel)
        ->assertSee('No transactions found for ' . $prevLabel);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.current-account.export', ['currency' => 'GBP', 'month' => now()->format('Y-m')]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
