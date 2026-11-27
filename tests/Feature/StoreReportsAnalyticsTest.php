<?php

use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function spRepMakeStore(string $name): Store
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
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);
}

function spRepMakeUser(Store $store, string $email): StoreUser
{
    $phone = '77009' . random_int(10000, 99999);

    return StoreUser::create([
        'store_id' => $store->id,
        'name' => $store->name . ' Manager',
        'email' => $email,
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => StoreUser::normalizePhone($phone, '+44'),
        'password' => 'StorePass123!',
        'role' => 'owner',
        'is_active' => true,
    ]);
}

it('loads store reports page and all 6 ajax report tabs', function () {
    $store = spRepMakeStore('Reports Store');
    $user = spRepMakeUser($store, 'rep@example.com');

    $this->actingAs($user, 'store')
        ->get(route('store.reports.index'))
        ->assertOk()
        ->assertSee('Store Reports &amp; Analytics', false);

    foreach ([
        'store.reports.overview',
        'store.reports.financial',
        'store.reports.transactions',
        'store.reports.bookings',
        'store.reports.handovers',
        'store.reports.performance',
    ] as $routeName) {
        $this->actingAs($user, 'store')
            ->getJson(route($routeName))
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
});

it('enforces store isolation on transaction details and sanitizes csv export', function () {
    $storeA = spRepMakeStore('Store A');
    $storeB = spRepMakeStore('Store B');
    $userA = spRepMakeUser($storeA, 'a@example.com');

    $txA = StoreTransaction::create([
        'store_id' => $storeA->id,
        'currency' => 'GBP',
        'reference' => 'STX-A-001',
        'idempotency_key' => 'rep-a-1',
        'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
        'direction' => StoreTransaction::DIRECTION_CREDIT,
        'amount' => '50.00',
        'balance_after' => '50.00',
        'description' => '=CMD("calc")',
    ]);

    $txB = StoreTransaction::create([
        'store_id' => $storeB->id,
        'currency' => 'GBP',
        'reference' => 'STX-B-001',
        'idempotency_key' => 'rep-b-1',
        'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
        'direction' => StoreTransaction::DIRECTION_CREDIT,
        'amount' => '99.00',
        'balance_after' => '99.00',
        'description' => 'Store B secret',
    ]);

    $this->actingAs($userA, 'store')
        ->getJson(route('store.reports.transactions.show', $txA))
        ->assertOk()
        ->assertJsonPath('transaction.reference', 'STX-A-001');

    $this->actingAs($userA, 'store')
        ->getJson(route('store.reports.transactions.show', $txB))
        ->assertForbidden();

    $csvRes = $this->actingAs($userA, 'store')
        ->get(route('store.reports.export'));

    $csvRes->assertOk();
    $content = $csvRes->streamedContent();
    expect($content)->toContain('STX-A-001')
        ->toContain("'=CMD")
        ->not->toContain('STX-B-001');
});
