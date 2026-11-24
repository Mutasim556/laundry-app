<?php

use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreEarning;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makePortalStore(string $name, float $commissionRate = 20.00, string $status = 'active'): Store
{
    return Store::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
        'store_code' => 'STR-' . random_int(1000, 9999),
        'email' => 'store' . random_int(1000, 9999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '10 Deansgate',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => $status,
        'commission_type' => 'percentage',
        'commission_rate' => $commissionRate,
    ]);
}

function makePortalStoreUser(Store $store, string $email, string $phone = '7700900111', bool $active = true): StoreUser
{
    return StoreUser::create([
        'store_id' => $store->id,
        'name' => $store->name . ' Manager',
        'email' => $email,
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => StoreUser::normalizePhone($phone, '+44'),
        'password' => 'StorePass123!',
        'role' => 'owner',
        'is_active' => $active,
    ]);
}

function makePortalBooking(Store $store, bool $finalizedStageDone = false, string $currency = 'GBP'): Booking
{
    $completed = [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
    ];
    if ($finalizedStageDone) {
        $completed[Booking::STATUS_FINALIZED] = ['completed_at' => now()->toIso8601String()];
    }

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Store Portal Customer',
        'customer_email' => 'cust@example.com',
        'customer_phone' => '+447700900888',
        'customer_type' => 'guest',
        'address_snapshot' => ['full_address' => '22 Oxford Street, Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00', 'delivery_date' => '2026-11-24', 'delivery_slot' => '14:00 - 16:00'],
        'services_snapshot' => [['name' => 'Mixed Wash & Fold']],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $finalizedStageDone ? Booking::STATUS_FINALIZED : Booking::STATUS_STORE_ASSIGNED,
        'assigned_store_id' => $store->id,
        'completed_operational_stages' => $completed,
        'currency' => $currency,
        'estimated_amount_minor' => 10000,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'tax_minor' => 0,
        'discount_minor' => 0,
        'final_amount_minor' => 14700,
        'confirmed_at' => now(),
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '10 Deansgate, Manchester',
        'store_city_snapshot' => 'Manchester',
        'store_postcode_snapshot' => 'M1 2PF',
        'store_phone_snapshot' => '+441614960000',
        'customer_postcode_snapshot' => 'M1 2PF',
        'postcode_matched' => true,
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
        'sort_order' => 1,
    ]);

    return $booking;
}

test('store portal authentication supports email or international phone, rejects inactive accounts, and isolates guards', function () {
    $activeStore = makePortalStore('Central Laundry', 20.00, 'active');
    $user = makePortalStoreUser($activeStore, 'central@example.com', '7700900111', true);

    // Wrong password
    $this->post(route('store.login.submit'), ['login' => 'central@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors(['login']);
    expect(Auth::guard('store')->check())->toBeFalse();

    // Email login succeeds
    $this->post(route('store.login.submit'), ['login' => 'central@example.com', 'password' => 'StorePass123!'])
        ->assertRedirect(route('store.dashboard'));
    expect(Auth::guard('store')->check())->toBeTrue();

    // Store user cannot access customer My Orders or Admin bookings
    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('admin.bookings.index'))->assertRedirect(route('admin.login'));

    // Logout
    $this->post(route('store.logout'))->assertRedirect(route('store.login'));
    expect(Auth::guard('store')->check())->toBeFalse();

    // International phone login succeeds
    $this->post(route('store.login.submit'), ['login' => '+44 7700 900111', 'password' => 'StorePass123!'])
        ->assertRedirect(route('store.dashboard'));
    $this->post(route('store.logout'));

    // Inactive store user rejected
    $user->update(['is_active' => false]);
    $this->post(route('store.login.submit'), ['login' => 'central@example.com', 'password' => 'StorePass123!'])
        ->assertSessionHasErrors(['login']);

    // Inactive store rejected
    $user->update(['is_active' => true]);
    $activeStore->update(['status' => 'inactive']);
    $this->post(route('store.login.submit'), ['login' => 'central@example.com', 'password' => 'StorePass123!'])
        ->assertSessionHasErrors(['login']);
});

test('enforces strict booking ownership and updates visibility immediately on store reassignment', function () {
    $storeA = makePortalStore('Store Alpha', 20.00);
    $storeB = makePortalStore('Store Beta', 15.00);
    $userA = makePortalStoreUser($storeA, 'alpha@example.com', '7700900222');
    $userB = makePortalStoreUser($storeB, 'beta@example.com', '7700900333');

    $booking = makePortalBooking($storeA, false);

    $this->actingAs($userA, 'store')
        ->get(route('store.bookings.index'))
        ->assertStatus(200)
        ->assertSee($booking->reference);

    $this->actingAs($userB, 'store')
        ->get(route('store.bookings.index'))
        ->assertStatus(200)
        ->assertDontSee($booking->reference);

    $this->actingAs($userB, 'store')
        ->get(route('store.bookings.show', $booking))
        ->assertStatus(403);

    // Reassign to Store B (superseding Store A's current assignment)
    BookingStoreAssignment::where('booking_id', $booking->id)->update(['is_current' => false]);
    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $storeB->id,
        'store_name_snapshot' => $storeB->name,
        'store_address_snapshot' => '10 Deansgate, Manchester',
        'is_current' => true,
        'assigned_at' => now(),
    ]);
    $booking->update(['assigned_store_id' => $storeB->id]);

    $this->actingAs($userA, 'store')
        ->get(route('store.bookings.show', $booking))
        ->assertStatus(403);

    $this->actingAs($userB, 'store')
        ->get(route('store.bookings.show', $booking))
        ->assertStatus(200)
        ->assertSee($booking->reference);
});

test('hides prices before Finalized stage is Done and calculates net store earnings excluding fees with immutable commission snapshot', function () {
    $store = makePortalStore('Piccadilly Store', 20.00);
    $user = makePortalStoreUser($store, 'piccadilly@example.com', '7700900444');

    // Before Finalized stage is Done (even though operational items total 100.00)
    $unfinalized = makePortalBooking($store, false, 'GBP');
    $this->actingAs($user, 'store')
        ->get(route('store.bookings.show', $unfinalized))
        ->assertStatus(200)
        ->assertSee('Mixed Wash &amp; Fold', false)
        ->assertSee('Pricing not finalized')
        ->assertDontSee('GBP 100.00')
        ->assertDontSee('GBP 80.00')
        ->assertDontSee('GBP 40.00')
        ->assertDontSee('GBP 7.00');

    // After Finalized stage is Done: 100.00 service base - 20% commission (20.00) = 80.00 net (excludes 7.00 service fee & 40.00 delivery fee)
    $finalized = makePortalBooking($store, true, 'GBP');
    $this->actingAs($user, 'store')
        ->get(route('store.bookings.show', $finalized))
        ->assertStatus(200)
        ->assertSee('GBP 100.00')
        ->assertSee('20.00%')
        ->assertSee('GBP 20.00')
        ->assertSee('GBP 80.00')
        ->assertDontSee('GBP 147.00')
        ->assertDontSee('GBP 40.00');

    $snapshot = BookingStoreEarning::where('booking_id', $finalized->id)->where('is_current', true)->first();
    expect($snapshot)->not->toBeNull()
        ->and($snapshot->eligible_service_amount_minor)->toBe(10000)
        ->and($snapshot->commission_amount_minor)->toBe(2000)
        ->and($snapshot->net_store_earnings_minor)->toBe(8000);

    // Changing store commission_rate later does NOT retroactively change finalized snapshot
    $store->update(['commission_rate' => 35.00]);
    $this->actingAs($user, 'store')
        ->get(route('store.earnings.index'))
        ->assertStatus(200)
        ->assertSee('GBP 80.00')
        ->assertDontSee('GBP 65.00');
});

test('admin can manage Store Portal users from Store Management', function () {
    Permission::firstOrCreate(['name' => 'edit-stores', 'guard_name' => 'admin']);
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-stores');
    $store = makePortalStore('Admin Managed Store', 18.00);

    $res = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.stores.users.store', $store), [
            'name' => 'New Store Staff',
            'email' => 'staff@example.com',
            'phone_country_code' => '+44',
            'phone' => '7700900555',
            'role' => 'manager',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $userId = $res->json('user_id');
    expect(StoreUser::find($userId)?->phone_normalized)->toBe('+447700900555');

    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.stores.users.toggle', [$store, $userId]))
        ->assertStatus(200)
        ->assertJsonPath('is_active', false);
});
