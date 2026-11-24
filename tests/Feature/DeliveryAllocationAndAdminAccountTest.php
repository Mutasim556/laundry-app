<?php

use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Models\BookingFinancialSettlement;
use App\Models\BookingOperationalItem;
use App\Models\StoreAccount;
use App\Services\AdminAccountService;
use App\Services\BookingFinancialSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-store-accounts', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'adjust-store-accounts', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-admin-accounts', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'adjust-admin-accounts', 'guard_name' => 'admin']);
});

function makeAdminWithPermissions(array $perms): Admin
{
    $admin = Admin::create([
        'name' => 'Finance Admin ' . random_int(100, 999),
        'email' => 'finadmin' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo($perms);

    return $admin;
}

function makeActiveStoreForFinance(float $commissionRate = 20.00): Store
{
    return Store::create([
        'name' => 'Hub Store ' . random_int(100, 999),
        'slug' => 'hub-store-' . random_int(1000, 9999),
        'store_code' => 'HUB-' . random_int(1000, 9999),
        'email' => 'hub' . random_int(1000, 9999) . '@example.com',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '10 Market St',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => $commissionRate,
    ]);
}

function makeCourier(string $name, string $postcode = 'M1 2PF'): DeliveryPerson
{
    $person = DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'phone' => '0770090' . random_int(1000, 9999),
        'email' => 'dp' . random_int(1000, 9999) . '@example.com',
        'address_line_1' => '25 High Street',
        'city' => 'Manchester',
        'residential_postcode' => $postcode,
        'vehicle_type' => 'van',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'per_collection_rate_minor' => 999,
        'per_delivery_rate_minor' => 888,
    ]);

    DeliveryPersonPostcode::create([
        'delivery_person_id' => $person->id,
        'postcode' => $postcode,
        'postcode_compact' => strtoupper(str_replace(' ', '', $postcode)),
    ]);

    return $person;
}

function makeWorkflowBooking(
    Store $store,
    int $deliveryFeeMinor = 1000,
    int $tipMinor = 400,
    string $paymentStatus = 'unpaid',
    string $stage = Booking::STATUS_DELIVERY_MAN_ASSIGNED,
    array $completed = []
): Booking {
    $subtotalMinor = 5000;
    $serviceFeeMinor = 500;
    $finalMinor = $subtotalMinor + $serviceFeeMinor + $deliveryFeeMinor + $tipMinor;

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Finance Customer',
        'customer_email' => 'fin-cust@example.com',
        'customer_phone' => '+447700900111',
        'address_snapshot' => ['address_line_1' => '12 King St', 'city' => 'Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'collection_slot_label' => '10:00 - 12:00',
        'delivery_slot_label' => '14:00 - 16:00',
        'currency' => 'GBP',
        'estimated_amount_minor' => $finalMinor,
        'service_fee_minor' => $serviceFeeMinor,
        'collection_delivery_fee_minor' => $deliveryFeeMinor,
        'tip_amount_minor' => $tipMinor,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'final_amount_minor' => $finalMinor,
        'payment_status' => $paymentStatus,
        'payment_gateway' => $paymentStatus === 'paid' ? 'stripe' : 'cod',
        'booking_status' => $stage,
        'completed_operational_stages' => $completed,
        'assigned_store_id' => $store->id,
        'version' => 1,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_code_snapshot' => $store->store_code,
        'store_address_snapshot' => '10 Market St, Manchester, M1 2PF',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    $service = Service::create([
        'name' => 'Dry Clean ' . random_int(100, 999),
        'slug' => 'dry-clean-' . random_int(1000, 9999),
        'is_active' => true,
        'display_order' => 1,
    ]);
    $category = ItemCategory::create([
        'name' => 'Suits ' . random_int(100, 999),
        'slug' => 'suits-' . random_int(1000, 9999),
        'is_active' => true,
        'display_order' => 1,
    ]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $service->id,
        'service_name' => $service->name,
        'item_category_id' => $category->id,
        'category_name' => $category->name,
        'item_name' => 'Suit',
        'quantity' => 1,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => $subtotalMinor,
        'actual_unit_price_minor' => $subtotalMinor,
        'line_total_minor' => $subtotalMinor,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    return $booking;
}

it('defaults delivery payment and customer tip to deterministic 50/50 splits including odd minor units', function () {
    $even = BookingFinancialSettlementService::splitMinorHalf(1000);
    expect($even)->toBe(['collection' => 500, 'final' => 500]);

    $odd = BookingFinancialSettlementService::splitMinorHalf(1001);
    expect($odd)->toBe(['collection' => 500, 'final' => 501]);

    $admin = makeAdminWithPermissions(['view-bookings', 'manage-bookings']);
    $store = makeActiveStoreForFinance();
    $courierA = makeCourier('Courier Alpha');
    $courierB = makeCourier('Courier Beta');
    $booking = makeWorkflowBooking($store, 1001, 301);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $courierA->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        ])
        ->assertOk()
        ->assertJsonPath('rate_minor', 500)
        ->assertJsonPath('tip_share_minor', 150)
        ->assertJsonPath('total_expected_earnings_minor', 650);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $courierB->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ])
        ->assertOk()
        ->assertJsonPath('rate_minor', 501)
        ->assertJsonPath('tip_share_minor', 151)
        ->assertJsonPath('total_expected_earnings_minor', 652);
});

it('allows manual delivery payment override and settles store, delivery persons, and positive admin margin atomically on Completed', function () {
    $admin = makeAdminWithPermissions(['view-bookings', 'manage-bookings', 'view-admin-accounts']);
    $store = makeActiveStoreForFinance(20.00);
    $courierA = makeCourier('Col Driver');
    $courierB = makeCourier('Fin Driver');

    $booking = makeWorkflowBooking(
        $store,
        1000, // 10.00 delivery fee
        500,  // 5.00 customer tip -> 2.50 / 2.50
        'paid',
        Booking::STATUS_ON_DELIVERY,
        [Booking::STATUS_FINALIZED]
    );

    // Assign collection with manual override 3.00 (300 minor) and final with 4.00 (400 minor) -> Admin margin = 1000 - 700 = 300 (3.00)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $courierA->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'delivery_payment' => '3.00',
        ])
        ->assertOk()
        ->assertJsonPath('rate_minor', 300);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $courierB->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
            'delivery_payment' => '4.00',
        ])
        ->assertOk()
        ->assertJsonPath('rate_minor', 400);

    // Before Completed is marked Done: NO ledger postings should exist
    expect(AdminAccountTransaction::count())->toBe(0);
    expect(DB::table('delivery_person_ledger_entries')->count())->toBe(0);
    expect(StoreAccount::count())->toBe(0);

    // Awaiting advance settlement should include this paid booking before completion
    $summaryBefore = app(AdminAccountService::class)->getSummaryByCurrency('GBP');
    expect($summaryBefore['GBP']['awaiting_advance_minor'])->toBe(7000);

    // Advance On Delivery -> Completed, then mark Completed Done
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_ON_DELIVERY,
            'version' => 1,
        ])
        ->assertRedirect();

    $booking->refresh();
    expect(AdminAccountTransaction::count())->toBe(0);

    // Mark Completed Done via controller
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_COMPLETED,
            'version' => 2,
        ])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_COMPLETED);
    expect(in_array(Booking::STATUS_COMPLETED, $booking->getCompletedOperationalStatuses(), true))->toBeTrue();

    // Collection courier ledger: 300 + 250 = 550
    $colLedger = DB::table('delivery_person_ledger_entries')
        ->where('delivery_person_id', $courierA->id)
        ->first();
    expect($colLedger)->not->toBeNull();
    expect((int) $colLedger->amount_minor)->toBe(550);

    // Final courier ledger: 400 + 250 = 650
    $finLedger = DB::table('delivery_person_ledger_entries')
        ->where('delivery_person_id', $courierB->id)
        ->first();
    expect($finLedger)->not->toBeNull();
    expect((int) $finLedger->amount_minor)->toBe(650);

    // Store credited (5000 items * 80% = 40.00)
    $storeAccount = StoreAccount::where('store_id', $store->id)->first();
    expect($storeAccount)->not->toBeNull();
    expect((float) $storeAccount->current_balance)->toBe(40.00);

    // Admin Account credited with 3.00 delivery margin
    $adminAccount = AdminAccount::where('currency', 'GBP')->first();
    expect($adminAccount)->not->toBeNull();
    expect((float) $adminAccount->current_balance)->toBe(3.00);
    expect((float) $adminAccount->total_credited)->toBe(3.00);
    expect((float) $adminAccount->total_debited)->toBe(0.00);

    // Awaiting advance settlement should now be 0
    $summaryAfter = app(AdminAccountService::class)->getSummaryByCurrency('GBP');
    expect($summaryAfter['GBP']['awaiting_advance_minor'])->toBe(0);

    // Idempotent duplicate call creates 0 duplicate entries
    app(BookingFinancialSettlementService::class)->settleCompletedBooking($booking, $admin->id);
    expect(AdminAccountTransaction::count())->toBe(1);
    expect(DB::table('delivery_person_ledger_entries')->count())->toBe(2);
    expect(BookingFinancialSettlement::count())->toBe(1);

    // Reconcile reports 0 issues
    $reconcile = app(AdminAccountService::class)->reconcileAll();
    expect($reconcile['issues'])->toBeEmpty();
});

it('debits Admin Account on delivery shortfall and skips monetary entry when margin is zero', function () {
    $admin = makeAdminWithPermissions(['view-bookings', 'manage-bookings']);
    $store = makeActiveStoreForFinance();
    $courier = makeCourier('Single Driver');

    // Booking 1: delivery fee 10.00, payout 7.00 + 6.00 = 13.00 -> shortfall 3.00
    $booking1 = makeWorkflowBooking($store, 1000, 200, 'unpaid', Booking::STATUS_ON_DELIVERY, [Booking::STATUS_FINALIZED]);
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking1), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'delivery_payment' => '7.00',
    ])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking1), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'delivery_payment' => '6.00',
    ])->assertOk();
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking1), [
        'expected_stage' => Booking::STATUS_ON_DELIVERY,
    ])->assertRedirect();
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking1), [
        'expected_stage' => Booking::STATUS_COMPLETED,
    ])->assertRedirect();

    // Same courier handled both legs -> 2 separate ledger entries
    expect(DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $courier->id)->count())->toBe(2);

    $adminAccount = AdminAccount::where('currency', 'GBP')->first();
    expect((float) $adminAccount->current_balance)->toBe(-3.00);
    expect((float) $adminAccount->total_debited)->toBe(3.00);

    // Booking 2: exact 50/50 split -> margin 0 -> 0 new AdminAccountTransaction
    $booking2 = makeWorkflowBooking($store, 1000, 0, 'unpaid', Booking::STATUS_ON_DELIVERY, [Booking::STATUS_FINALIZED]);
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking2), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
    ])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking2), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
    ])->assertOk();
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking2), [
        'expected_stage' => Booking::STATUS_ON_DELIVERY,
    ])->assertRedirect();
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking2), [
        'expected_stage' => Booking::STATUS_COMPLETED,
    ])->assertRedirect();

    expect(AdminAccountTransaction::count())->toBe(1); // Only booking1 shortfall
    expect(BookingFinancialSettlement::where('booking_id', $booking2->id)->value('admin_delivery_margin_minor'))->toBe(0);
});

it('enforces admin account permissions, immutability, and compensating reversals', function () {
    $viewer = makeAdminWithPermissions(['view-admin-accounts']);
    $adjuster = makeAdminWithPermissions(['view-admin-accounts', 'adjust-admin-accounts']);
    $unauthorized = makeAdminWithPermissions(['view-bookings']);

    $this->actingAs($unauthorized, 'admin')
        ->get(route('admin.current-account.index'))
        ->assertForbidden();

    $this->actingAs($viewer, 'admin')
        ->get(route('admin.current-account.index'))
        ->assertOk()
        ->assertSee('Admin Current Account');

    $this->actingAs($viewer, 'admin')
        ->post(route('admin.current-account.adjust'), [
            'currency' => 'GBP',
            'direction' => 'credit',
            'amount' => '25.00',
            'description' => 'Viewer cannot adjust',
        ])
        ->assertForbidden();

    $this->actingAs($adjuster, 'admin')
        ->post(route('admin.current-account.adjust'), [
            'currency' => 'GBP',
            'direction' => 'credit',
            'amount' => '25.00',
            'description' => 'Opening float credit',
        ])
        ->assertRedirect();

    $tx = AdminAccountTransaction::firstOrFail();
    expect((float) $tx->balance_after)->toBe(25.00);

    // Immutability check
    expect(fn () => $tx->update(['description' => 'Tampered']))->toThrow(RuntimeException::class);
    expect(fn () => $tx->delete())->toThrow(RuntimeException::class);

    // Compensating reversal
    $this->actingAs($adjuster, 'admin')
        ->post(route('admin.current-account.reverse', $tx), [
            'reason' => 'Reversed float credit',
        ])
        ->assertRedirect();

    $account = AdminAccount::where('currency', 'GBP')->firstOrFail();
    expect((float) $account->current_balance)->toBe(0.00);
    expect(AdminAccountTransaction::count())->toBe(2);

    // Double reversal prevented
    $this->actingAs($adjuster, 'admin')
        ->post(route('admin.current-account.reverse', $tx), [
            'reason' => 'Second reversal attempt',
        ])
        ->assertSessionHasErrors('reversal');
});
