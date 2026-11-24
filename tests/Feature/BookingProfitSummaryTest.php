<?php

use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use App\Services\BookingFinancialSettlementService;
use App\Services\BookingProfitService;
use App\Services\CurrencyService;
use App\Services\StoreEarningsService;
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
});

function makeProfitAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Profit Admin',
        'email' => 'profitadmin' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    return $admin;
}

function makeProfitStore(float $rate = 20.00): Store
{
    return Store::create([
        'name' => 'Profit Store ' . random_int(100, 999),
        'slug' => 'profit-store-' . random_int(1000, 9999),
        'store_code' => 'PRF-' . random_int(1000, 9999),
        'email' => 'prf' . random_int(1000, 9999) . '@example.com',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '10 Market St',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => $rate,
    ]);
}

function makeProfitCourier(string $name): DeliveryPerson
{
    $person = DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'phone' => '0770090' . random_int(1000, 9999),
        'email' => 'dp' . random_int(1000, 9999) . '@example.com',
        'address_line_1' => '25 High Street',
        'city' => 'Manchester',
        'residential_postcode' => 'M1 2PF',
        'vehicle_type' => 'van',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'per_collection_rate_minor' => 500,
        'per_delivery_rate_minor' => 500,
    ]);

    DeliveryPersonPostcode::create([
        'delivery_person_id' => $person->id,
        'postcode' => 'M1 2PF',
        'postcode_compact' => 'M12PF',
    ]);

    return $person;
}

function makeProfitBooking(
    ?Store $store,
    int $itemsMinor = 75000,
    int $serviceFeeMinor = 700,
    int $deliveryFeeMinor = 4000,
    int $tipMinor = 1000,
    int $discountMinor = 0,
    int $taxMinor = 0,
    int $refundedMinor = 0,
    string $stage = Booking::STATUS_FINALIZED,
    array $completed = [Booking::STATUS_FINALIZED]
): Booking {
    $finalMinor = max(0, $itemsMinor + $serviceFeeMinor + $deliveryFeeMinor + $tipMinor + $taxMinor - $discountMinor);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Profit Customer',
        'customer_email' => 'profit-cust@example.com',
        'customer_phone' => '+447700900111',
        'address_snapshot' => ['address_line_1' => '12 King St', 'city' => 'Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'currency' => 'GBP',
        'estimated_amount_minor' => $finalMinor,
        'service_fee_minor' => $serviceFeeMinor,
        'collection_delivery_fee_minor' => $deliveryFeeMinor,
        'tip_amount_minor' => $tipMinor,
        'discount_minor' => $discountMinor,
        'tax_minor' => $taxMinor,
        'refunded_amount_minor' => $refundedMinor,
        'final_amount_minor' => $finalMinor,
        'payment_status' => 'paid',
        'payment_gateway' => 'stripe',
        'booking_status' => $stage,
        'completed_operational_stages' => $completed,
        'assigned_store_id' => $store?->id,
        'version' => 1,
    ]);

    if ($store) {
        BookingStoreAssignment::create([
            'booking_id' => $booking->id,
            'store_id' => $store->id,
            'store_name_snapshot' => $store->name,
            'store_code_snapshot' => $store->store_code,
            'store_address_snapshot' => '10 Market St, Manchester, M1 2PF',
            'assigned_at' => now(),
            'is_current' => true,
        ]);
    }

    if ($itemsMinor > 0) {
        $cat = ItemCategory::create(['name' => 'Dry Clean ' . random_int(100, 999), 'slug' => 'dc-' . random_int(1000, 9999), 'is_active' => true]);
        $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Suits ' . random_int(100, 999), 'slug' => 'suits-' . random_int(1000, 9999), 'pricing_mode' => 'fixed', 'price_minor' => $itemsMinor, 'is_active' => true]);

        BookingOperationalItem::create([
            'booking_id' => $booking->id,
            'service_id' => $srv->id,
            'service_name' => $srv->name,
            'item_category_id' => $cat->id,
            'category_name' => $cat->name,
            'item_name' => 'Suit',
            'quantity' => 1,
            'pricing_mode' => 'fixed',
            'catalog_unit_price_minor' => $itemsMinor,
            'actual_unit_price_minor' => $itemsMinor,
            'line_total_minor' => $itemsMinor,
            'currency' => 'GBP',
            'is_active' => true,
        ]);
    }

    return $booking;
}

it('calculates finalized booking contribution profit accurately without double-counting commission or tips, and reads without mutating balances', function () {
    $admin = makeProfitAdmin();
    $store = makeProfitStore(20.00);
    $courierA = makeProfitCourier('Courier One');
    $courierB = makeProfitCourier('Courier Two');

    $booking = makeProfitBooking($store, 75000, 700, 4000, 1000, 0, 0, 0, Booking::STATUS_FINALIZED, [Booking::STATUS_FINALIZED]);

    app(StoreEarningsService::class)->getOrCreateSnapshotForBooking($booking, $admin->id);

    // Assign couriers with default 50/50 split (20.00 + 20.00)
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking), [
        'delivery_person_id' => $courierA->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
    ])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking), [
        'delivery_person_id' => $courierB->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
    ])->assertOk();

    // Change store commission rate AFTER finalization -> saved snapshot must preserve 20%
    $store->update(['commission_rate' => 50.00]);

    $profit = app(BookingProfitService::class)->calculateForBooking($booking->fresh());

    expect($profit['status_label'])->toBe('Finalized Booking Profit');
    expect($profit['eligible_service_revenue_minor'])->toBe(75000);
    expect($profit['store_net_earnings_minor'])->toBe(60000);
    expect($profit['store_contribution_minor'])->toBe(15000); // 20% preserved, NOT 50%
    expect($profit['delivery_contribution_minor'])->toBe(0);
    expect($profit['service_fee_minor'])->toBe(700);
    expect($profit['customer_tip_minor'])->toBe(1000);
    expect($profit['allocated_tips_minor'])->toBe(1000);
    expect($profit['has_tip_reconciliation_issue'])->toBeFalse();
    expect($profit['total_profit_minor'])->toBe(15700); // GBP 157.00
    expect($profit['recognized_net_revenue_minor'])->toBe(79700); // 750 + 7 + 40 (tip excluded)
    expect($profit['margin_percentage_formatted'])->toBe('19.70%');

    // Opening Admin Booking Details must render the profit card and NOT mutate any account balances
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Booking Profit Summary')
        ->assertSee('Finalized Booking Profit')
        ->assertSee('19.70%');

    expect(AdminAccount::count())->toBe(0);
    expect(AdminAccountTransaction::count())->toBe(0);
    expect(StoreAccount::count())->toBe(0);
    expect(StoreTransaction::count())->toBe(0);
    expect(DB::table('delivery_person_ledger_entries')->count())->toBe(0);
});

it('handles negative profit, discounts, excluded authority taxes, refunds, zero-revenue N/A margin, and unfinalized visibility', function () {
    $admin = makeProfitAdmin();
    $store = makeProfitStore(10.00);
    $courier = makeProfitCourier('Overpaid Courier');

    // Case 1: Negative profit due to delivery shortfall + discount + refund, while authority tax is excluded
    $booking = makeProfitBooking(
        $store,
        10000, // 100.00 items -> 10.00 store contribution
        500,   // 5.00 service fee
        1000,  // 10.00 delivery fee
        301,   // 3.01 odd-cent tip -> 1.50 + 1.51 = 3.01 pass-through
        2000,  // 20.00 discount
        1500,  // 15.00 authority tax (excluded)
        1000,  // 10.00 refund
        Booking::STATUS_ON_DELIVERY,
        [Booking::STATUS_FINALIZED]
    );

    app(StoreEarningsService::class)->getOrCreateSnapshotForBooking($booking, $admin->id);
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'delivery_payment' => '15.00',
    ])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.bookings.assign-courier', $booking), [
        'delivery_person_id' => $courier->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'delivery_payment' => '10.00',
    ])->assertOk()
        ->assertJsonPath('profit_summary.total_profit_minor', -3000); // AJAX response carries updated profit

    $profit = app(BookingProfitService::class)->calculateForBooking($booking->fresh());
    // Store: 1000, Delivery: 1000 - 1500 - 1000 = -1500, Other: 500 - 2000 - 1000 = -2500 -> Total: -3000 (-30.00)
    // Recognized net revenue: 10000 + 500 + 1000 - 2000 - 1000 = 8500 (tax 1500 & tip 301 excluded)
    expect($profit['total_profit_minor'])->toBe(-3000);
    expect($profit['delivery_contribution_minor'])->toBe(-1500);
    expect($profit['recognized_net_revenue_minor'])->toBe(8500);
    expect($profit['margin_percentage_formatted'])->toBe('-35.29%');

    // After completion settlement -> label becomes Earned / Posted Booking Profit
    $booking->update(['booking_status' => Booking::STATUS_COMPLETED]);
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking), [
        'expected_stage' => Booking::STATUS_COMPLETED,
    ])->assertRedirect();
    $postedProfit = app(BookingProfitService::class)->calculateForBooking($booking->fresh());
    expect($postedProfit['status_label'])->toBe('Earned / Posted Booking Profit');

    // Case 2: Unfinalized & zero revenue -> Profit Not Finalized + N/A margin (no div by zero)
    $unfinalized = makeProfitBooking(null, 0, 0, 0, 0, 0, 0, 0, Booking::STATUS_PROCESSING, []);
    $unfinalizedProfit = app(BookingProfitService::class)->calculateForBooking($unfinalized);
    expect($unfinalizedProfit['status_label'])->toBe('Profit Not Finalized');
    expect($unfinalizedProfit['margin_percentage_formatted'])->toBe('N/A');
});
