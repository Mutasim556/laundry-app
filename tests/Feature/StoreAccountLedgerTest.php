<?php

use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use App\Services\StoreAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
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
});

function createLedgerStore(string $name, float $rate = 20.00): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
        'store_code' => 'STR-' . random_int(1000, 9999),
        'email' => 'ledger' . random_int(1000, 9999) . '@example.com',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '10 Deansgate',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => $rate,
    ]);

    $storeUser = StoreUser::create([
        'store_id' => $store->id,
        'name' => $name . ' Owner',
        'email' => 'owner' . random_int(1000, 9999) . '@example.com',
        'password' => 'Secret123!',
        'role' => 'owner',
        'is_active' => true,
    ]);

    return [$store, $storeUser];
}

function createLedgerBooking(Store $store, string $stage = Booking::STATUS_FINALIZED, array $completed = []): Booking
{
    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Ledger Customer',
        'customer_email' => 'ledger-cust@example.com',
        'customer_phone' => '+447700900111',
        'customer_type' => 'guest',
        'address_snapshot' => ['full_address' => '10 Oxford Rd', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [['name' => 'Mixed Wash & Fold']],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $stage,
        'assigned_store_id' => $store->id,
        'completed_operational_stages' => $completed,
        'currency' => 'GBP',
        'estimated_amount_minor' => 10000,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'final_amount_minor' => 14700,
        'confirmed_at' => now(),
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '10 Deansgate, Manchester',
        'is_current' => true,
        'assigned_at' => now(),
    ]);

    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold', 'is_active' => true]);
    $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Mixed Wash & Fold', 'slug' => 'mwf-' . random_int(1000, 9999), 'pricing_mode' => 'weight', 'price_minor' => 10000, 'is_active' => true]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $srv->id,
        'item_category_id' => $cat->id,
        'service_name' => 'Mixed Wash & Fold',
        'category_name' => 'Wash & Fold',
        'pricing_mode' => 'weight',
        'weight_kg' => 1.00,
        'catalog_unit_price_minor' => 10000,
        'actual_unit_price_minor' => 10000,
        'line_total_minor' => 10000,
        'is_active' => true,
    ]);

    return $booking;
}

test('keeps earnings pending at Finalized and credits store balance only when final Completed stage is Done', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    [$store, $storeUser] = createLedgerStore('Piccadilly Store', 20.00);

    $booking = createLedgerBooking($store, Booking::STATUS_FINALIZED, [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => now()->toIso8601String()],
    ]);

    // Mark Finalized Done -> Pending Earnings = 80.00, Current Balance = 0.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_FINALIZED,
            'version' => $booking->version,
        ])
        ->assertSessionHas('success');

    $accountService = app(StoreAccountService::class);
    $summaryAtFinalized = $accountService->getStoreAccountSummary($store);
    expect($summaryAtFinalized['GBP']['pending_earnings_minor'])->toBe(8000)
        ->and($summaryAtFinalized['GBP']['current_balance_minor'])->toBe(0)
        ->and(StoreTransaction::where('store_id', $store->id)->count())->toBe(0);

    // Advance intermediate stages up to On Delivery -> still NOT credited
    $booking->refresh();
    $booking->update(['booking_status' => Booking::STATUS_ON_DELIVERY]);
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_ON_DELIVERY,
            'version' => $booking->version,
        ])
        ->assertSessionHas('success');

    expect(StoreTransaction::where('store_id', $store->id)->count())->toBe(0);

    // Mark final Completed stage Done -> credited 80.00 atomically!
    $booking->refresh();
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_COMPLETED,
            'version' => $booking->version,
        ])
        ->assertSessionHas('success');

    $summaryAfterCompleted = $accountService->getStoreAccountSummary($store);
    expect($summaryAfterCompleted['GBP']['current_balance_minor'])->toBe(8000)
        ->and($summaryAfterCompleted['GBP']['total_earned_minor'])->toBe(8000)
        ->and($summaryAfterCompleted['GBP']['pending_earnings_minor'])->toBe(0)
        ->and(StoreTransaction::where('store_id', $store->id)->count())->toBe(1);

    // Duplicate prevention: calling creditCompletedBooking again creates NO duplicate transaction
    $accountService->creditCompletedBooking($booking->fresh(), $admin->id);
    expect(StoreTransaction::where('store_id', $store->id)->count())->toBe(1);
    expect((string) StoreAccount::where('store_id', $store->id)->value('current_balance'))->toBe('80.00');

    // Store Panel /store/account displays balance and transaction
    $this->actingAs($storeUser, 'store')
        ->get(route('store.account.index'))
        ->assertStatus(200)
        ->assertSee('GBP 80.00')
        ->assertSee($booking->reference)
        ->assertSee('Booking Earning');

    // Reconciliation passes cleanly
    expect($accountService->reconcileAll()['ok'])->toBeTrue();
});

test('enforces transaction immutability, compensating reversals, manual adjustments, and cross-store isolation', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-store-accounts', 'adjust-store-accounts']);
    [$storeA, $userA] = createLedgerStore('Store A', 20.00);
    [$storeB, $userB] = createLedgerStore('Store B', 20.00);

    $accountService = app(StoreAccountService::class);
    $tx = $accountService->createManualAdjustment($storeA, 'GBP', 5000, 'credit', 'Opening promo bonus', $admin->id);

    // Immutable: cannot update or delete StoreTransaction
    expect(fn () => $tx->update(['amount' => '999.00']))->toThrow(RuntimeException::class);
    expect(fn () => $tx->delete())->toThrow(RuntimeException::class);

    // Store B cannot see Store A's transaction
    $this->actingAs($userB, 'store')
        ->get(route('store.account.index'))
        ->assertStatus(200)
        ->assertDontSee($tx->reference);

    // Admin compensating reversal brings balance back to 0.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.stores.account.reverse', [$storeA, $tx]), ['reason' => 'Reversed duplicate bonus'])
        ->assertSessionHas('success');

    $summary = $accountService->getStoreAccountSummary($storeA);
    expect($summary['GBP']['current_balance_minor'])->toBe(0)
        ->and(StoreTransaction::where('store_id', $storeA->id)->count())->toBe(2)
        ->and($accountService->reconcileAll()['ok'])->toBeTrue();
});
