<?php

use App\Models\Booking;
use App\Models\BookingStoreHandover;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function spMonthMakeStore(string $name = 'Month Test Store'): Store
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

function spMonthMakeUser(Store $store, string $email): StoreUser
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

function spMonthMakeBooking(Store $store, string $reference, Carbon $assignedAt, string $status = Booking::STATUS_STORE_ASSIGNED): Booking
{
    $booking = Booking::create([
        'reference' => $reference,
        'customer_name' => 'Customer ' . $reference,
        'customer_email' => 'cust' . random_int(100, 999) . '@example.com',
        'customer_phone' => '+447700900888',
        'customer_type' => 'guest',
        'address_snapshot' => ['full_address' => '22 Oxford Street, Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => $assignedAt->toDateString(), 'collection_slot' => '10:00 - 12:00', 'delivery_date' => $assignedAt->copy()->addDay()->toDateString(), 'delivery_slot' => '14:00 - 16:00'],
        'services_snapshot' => [['name' => 'Mixed Wash & Fold']],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $status,
        'assigned_store_id' => $store->id,
        'completed_operational_stages' => [
            Booking::STATUS_PROCESSING => ['completed_at' => $assignedAt->toIso8601String()],
            Booking::STATUS_STORE_ASSIGNED => ['completed_at' => $assignedAt->toIso8601String()],
        ],
        'currency' => 'GBP',
        'estimated_amount_minor' => 10000,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'tax_minor' => 0,
        'discount_minor' => 0,
        'final_amount_minor' => 14700,
        'confirmed_at' => $assignedAt,
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
        'assigned_at' => $assignedAt,
    ]);

    return $booking;
}

function spMonthMakeDp(string $name = 'Alex Courier'): DeliveryPerson
{
    $phone = '077007' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'email' => 'dp' . random_int(1000, 9999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '10 High St',
        'city' => 'Manchester',
        'residential_postcode' => 'M1 2PF',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Pass1234!',
    ]);
}

it('filters store dashboard month-wise via default load and ajax', function () {
    $tz = config('app.timezone', 'UTC');
    $now = now($tz);
    $prev = $now->copy()->startOfMonth()->subMonth();

    $store = spMonthMakeStore('Dash Store');
    $otherStore = spMonthMakeStore('Other Store');
    $user = spMonthMakeUser($store, 'dash@example.com');

    spMonthMakeBooking($store, 'LND-CURR-001', $now->copy()->startOfMonth()->addDays(1));
    spMonthMakeBooking($store, 'LND-PREV-001', $prev->copy()->startOfMonth()->addDays(2));
    spMonthMakeBooking($otherStore, 'LND-OTHER-001', $now->copy()->startOfMonth()->addDays(1));

    $this->actingAs($user, 'store')
        ->get(route('store.dashboard'))
        ->assertOk()
        ->assertSee('LND-CURR-001')
        ->assertDontSee('LND-PREV-001')
        ->assertDontSee('LND-OTHER-001');

    $res = $this->actingAs($user, 'store')
        ->getJson(route('store.dashboard', [
            'month' => $prev->month,
            'year' => $prev->year,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $res->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('month', $prev->month)
        ->assertJsonPath('year', $prev->year);

    expect($res->json('results_html'))->toContain('LND-PREV-001')
        ->not->toContain('LND-CURR-001')
        ->not->toContain('LND-OTHER-001');
});

it('filters store bookings month-wise with active_only, search, status, and store isolation', function () {
    $tz = config('app.timezone', 'UTC');
    $now = now($tz);
    $prev = $now->copy()->startOfMonth()->subMonth();

    $store = spMonthMakeStore('Bookings Store');
    $otherStore = spMonthMakeStore('Isolated Store');
    $user = spMonthMakeUser($store, 'bookings@example.com');

    spMonthMakeBooking($store, 'LND-BK-CURR', $now->copy()->startOfMonth()->addDays(2), Booking::STATUS_STORE_PROCESSING);
    spMonthMakeBooking($store, 'LND-BK-PREV', $prev->copy()->startOfMonth()->addDays(3), Booking::STATUS_STORE_ASSIGNED);
    spMonthMakeBooking($otherStore, 'LND-BK-OTHER', $now->copy()->startOfMonth()->addDays(2), Booking::STATUS_STORE_ASSIGNED);

    $this->actingAs($user, 'store')
        ->get(route('store.bookings.index'))
        ->assertOk()
        ->assertSee('LND-BK-CURR')
        ->assertDontSee('LND-BK-PREV')
        ->assertDontSee('LND-BK-OTHER');

    $prevRes = $this->actingAs($user, 'store')
        ->getJson(route('store.bookings.index', [
            'month' => $prev->month,
            'year' => $prev->year,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $prevRes->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('summary.total', 1);
    expect($prevRes->json('results_html'))->toContain('LND-BK-PREV')->not->toContain('LND-BK-CURR');

    $activeRes = $this->actingAs($user, 'store')
        ->getJson(route('store.bookings.index', [
            'month' => $now->month,
            'year' => $now->year,
            'active_only' => '1',
            'q' => 'LND-BK-PREV',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $activeRes->assertOk()->assertJsonPath('summary.total', 1);
    expect($activeRes->json('results_html'))->toContain('LND-BK-PREV');
});

it('filters store handovers month-wise with pending_only and search via ajax', function () {
    $tz = config('app.timezone', 'UTC');
    $now = now($tz);
    $prev = $now->copy()->startOfMonth()->subMonth();

    $store = spMonthMakeStore('Handover Store');
    $otherStore = spMonthMakeStore('Other Handover Store');
    $user = spMonthMakeUser($store, 'handovers@example.com');
    $dp = spMonthMakeDp('Sam Rider');

    $bCurr = spMonthMakeBooking($store, 'LND-HO-CURR', $now->copy()->startOfMonth()->addDays(1));
    $bPrev = spMonthMakeBooking($store, 'LND-HO-PREV', $prev->copy()->startOfMonth()->addDays(1));
    $bOther = spMonthMakeBooking($otherStore, 'LND-HO-OTHER', $now->copy()->startOfMonth()->addDays(1));

    BookingStoreHandover::create([
        'request_reference' => 'REQ-CURR-01',
        'booking_id' => $bCurr->id,
        'store_id' => $store->id,
        'collection_delivery_person_id' => $dp->id,
        'status' => BookingStoreHandover::STATUS_ACCEPTED,
        'requested_at' => $now->copy()->startOfMonth()->addDays(1),
        'accepted_at' => $now->copy()->startOfMonth()->addDays(1),
        'idempotency_key' => 'ho-curr-' . $bCurr->id,
    ]);

    BookingStoreHandover::create([
        'request_reference' => 'REQ-PREV-01',
        'booking_id' => $bPrev->id,
        'store_id' => $store->id,
        'collection_delivery_person_id' => $dp->id,
        'status' => BookingStoreHandover::STATUS_PENDING,
        'requested_at' => $prev->copy()->startOfMonth()->addDays(2),
        'idempotency_key' => 'ho-prev-' . $bPrev->id,
    ]);

    BookingStoreHandover::create([
        'request_reference' => 'REQ-OTHER-01',
        'booking_id' => $bOther->id,
        'store_id' => $otherStore->id,
        'collection_delivery_person_id' => $dp->id,
        'status' => BookingStoreHandover::STATUS_PENDING,
        'requested_at' => $now->copy()->startOfMonth()->addDays(1),
        'idempotency_key' => 'ho-other-' . $bOther->id,
    ]);

    $this->actingAs($user, 'store')
        ->get(route('store.handovers.index'))
        ->assertOk()
        ->assertSee('REQ-CURR-01')
        ->assertDontSee('REQ-PREV-01')
        ->assertDontSee('REQ-OTHER-01');

    $pendingAllRes = $this->actingAs($user, 'store')
        ->getJson(route('store.handovers.index', [
            'month' => $now->month,
            'year' => $now->year,
            'pending_only' => '1',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $pendingAllRes->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('summary.pending', 1);
    expect($pendingAllRes->json('results_html'))->toContain('REQ-PREV-01')->not->toContain('REQ-OTHER-01');
});

it('filters store account monthly activity and transactions while keeping all-time balances intact', function () {
    $tz = config('app.timezone', 'UTC');
    $now = now($tz);
    $prev = $now->copy()->startOfMonth()->subMonth();

    $store = spMonthMakeStore('Ledger Store');
    $user = spMonthMakeUser($store, 'account@example.com');

    StoreAccount::create([
        'store_id' => $store->id,
        'currency' => 'GBP',
        'current_balance' => '180.00',
        'total_earned' => '180.00',
        'total_paid_out' => '0.00',
    ]);

    $bCurr = spMonthMakeBooking($store, 'LND-ACC-CURR', $now->copy()->startOfMonth()->addDays(1), Booking::STATUS_COMPLETED);
    $bPrev = spMonthMakeBooking($store, 'LND-ACC-PREV', $prev->copy()->startOfMonth()->addDays(1), Booking::STATUS_COMPLETED);

    $txCurr = new StoreTransaction([
        'store_id' => $store->id,
        'booking_id' => $bCurr->id,
        'currency' => 'GBP',
        'reference' => 'STX-CURR-100',
        'idempotency_key' => 'test-curr-' . $bCurr->id,
        'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
        'direction' => StoreTransaction::DIRECTION_CREDIT,
        'amount' => '100.00',
        'balance_after' => '180.00',
        'description' => 'Current month earning',
    ]);
    $txCurr->created_at = $now->copy()->startOfMonth()->addDays(2);
    $txCurr->updated_at = $txCurr->created_at;
    $txCurr->save();

    $txPrev = new StoreTransaction([
        'store_id' => $store->id,
        'booking_id' => $bPrev->id,
        'currency' => 'GBP',
        'reference' => 'STX-PREV-080',
        'idempotency_key' => 'test-prev-' . $bPrev->id,
        'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
        'direction' => StoreTransaction::DIRECTION_CREDIT,
        'amount' => '80.00',
        'balance_after' => '80.00',
        'description' => 'Previous month earning',
    ]);
    $txPrev->created_at = $prev->copy()->startOfMonth()->addDays(3);
    $txPrev->updated_at = $txPrev->created_at;
    $txPrev->save();

    $page = $this->actingAs($user, 'store')
        ->get(route('store.account.index'));

    $page->assertOk()
        ->assertSee('180.00')
        ->assertSee('STX-CURR-100')
        ->assertDontSee('STX-PREV-080');

    $prevRes = $this->actingAs($user, 'store')
        ->getJson(route('store.account.index', [
            'month' => $prev->month,
            'year' => $prev->year,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $prevRes->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('month', $prev->month)
        ->assertJsonPath('year', $prev->year);

    expect($prevRes->json('results_html'))->toContain('STX-PREV-080')->not->toContain('STX-CURR-100');
    expect($prevRes->json('monthly_html'))->toContain($prev->format('F Y'));
});
