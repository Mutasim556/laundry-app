<?php

use App\Models\AdminAccount;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreHandover;
use App\Models\StoreAccount;
use App\Models\StoreUser;
use App\Services\StoreHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
});

function makeHandoverStore(string $name): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
        'store_code' => 'HND-' . random_int(1000, 9999),
        'email' => strtolower(str_replace(' ', '', $name)) . random_int(100, 999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '23 Crowder Street',
        'city' => 'London',
        'postcode' => 'E1 0BJ',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $storeUser = StoreUser::create([
        'store_id' => $store->id,
        'name' => $name . ' Manager',
        'email' => 'mgr' . random_int(1000, 9999) . '@example.com',
        'password' => 'Secret123!',
        'role' => 'owner',
        'is_active' => true,
    ]);

    return [$store, $storeUser];
}

function makeHandoverDp(string $name): DeliveryPerson
{
    $phone = '077007' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'email' => strtolower(str_replace(' ', '.', $name)) . random_int(100, 999) . '@example.com',
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
        'password' => 'Driver2026',
    ]);
}

function makeHandoverBooking(Store $store, DeliveryPerson $colDp, ?DeliveryPerson $finDp = null, bool $pickedUpDone = true): Booking
{
    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Handover Customer',
        'customer_email' => 'hnd-cust@example.com',
        'customer_phone' => '+447700900333',
        'address_snapshot' => ['selected_address' => '18 London Road, M1 2PF', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-24', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2500,
        'service_fee_minor' => 200,
        'collection_delivery_fee_minor' => 300,
        'tip_amount_minor' => 0,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'final_amount_minor' => 2500,
        'payment_status' => 'paid',
        'payment_gateway' => 'stripe',
        'booking_status' => $pickedUpDone ? Booking::STATUS_STORE_RECEIVED : Booking::STATUS_DELIVERY_MAN_PICKED_UP,
        'completed_operational_stages' => $pickedUpDone
            ? [Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()], Booking::STATUS_DELIVERY_MAN_PICKED_UP => ['completed_at' => now()->toIso8601String()]]
            : [Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()]],
        'assigned_store_id' => $store->id,
        'version' => 1,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '23 Crowder Street, London',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    $cat = ItemCategory::create(['name' => 'Wash & Fold ' . random_int(100, 999), 'slug' => 'wf-' . random_int(100, 999), 'is_active' => true]);
    $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Wash Service', 'slug' => 'ws-' . random_int(100, 999), 'price' => 20.00, 'is_active' => true]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $srv->id,
        'service_name' => $srv->name,
        'item_category_id' => $cat->id,
        'category_name' => $cat->name,
        'item_name' => 'Laundry Bag',
        'quantity' => 1,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => 2000,
        'actual_unit_price_minor' => 2000,
        'line_total_minor' => 2000,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-HCOL-' . random_int(1000, 9999),
        'booking_id' => $booking->id,
        'delivery_person_id' => $colDp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => $pickedUpDone ? DeliveryAssignment::STATUS_PICKED_UP : DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_location' => '18 London Road',
        'destination_location' => $store->name,
        'assigned_at' => now(),
        'rate_minor' => 150,
        'currency' => 'GBP',
    ]);

    if ($finDp) {
        DeliveryAssignment::create([
            'assignment_reference' => 'ASG-HFIN-' . random_int(1000, 9999),
            'booking_id' => $booking->id,
            'delivery_person_id' => $finDp->id,
            'store_id' => $store->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'pickup_location' => $store->name,
            'destination_location' => '18 London Road',
            'assigned_at' => now(),
            'rate_minor' => 150,
            'currency' => 'GBP',
        ]);
    }

    return $booking;
}

it('hides Store Handover before Picked Up Done, blocks Final Delivery Person, and prevents duplicate handover requests', function () {
    [$store] = makeHandoverStore('Central Store');
    $colDp = makeHandoverDp('Col Driver');
    $finDp = makeHandoverDp('Fin Driver');
    $bookingNotPickedUp = makeHandoverBooking($store, $colDp, $finDp, false);

    // Before Picked Up Done: section absent and endpoint returns 422
    $this->actingAs($colDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $bookingNotPickedUp))
        ->assertOk()
        ->assertDontSee('Request Store Acceptance');

    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $bookingNotPickedUp))
        ->assertStatus(422);

    // Final Delivery Person gets 403 even after pickup
    $bookingReady = makeHandoverBooking($store, $colDp, $finDp, true);
    $this->actingAs($finDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $bookingReady))
        ->assertForbidden();

    // Collection Delivery Person sees full store info and submits request
    $this->actingAs($colDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $bookingReady))
        ->assertOk()
        ->assertSee('Store Handover')
        ->assertSee($store->name)
        ->assertSee('23 Crowder Street')
        ->assertSee('+44 1614960202')
        ->assertSee('Request Store Acceptance');

    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $bookingReady), [
            'idempotency_key' => 'req-key-1',
        ])
        ->assertOk()
        ->assertJsonPath('handover.status', BookingStoreHandover::STATUS_PENDING);

    // Store Received must NOT be done yet
    $bookingReady->refresh();
    expect(in_array(Booking::STATUS_STORE_RECEIVED, $bookingReady->getCompletedOperationalStatuses(), true))->toBeFalse();

    // Duplicate non-idempotent request rejected
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $bookingReady))
        ->assertStatus(422);
});

it('allows assigned Store to accept handover atomically completing Store Received without mutating financial accounts, and isolates other stores', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'adm-hnd@example.com', 'password' => bcrypt('Secret123!'), 'is_active' => true]);
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    [$storeA, $storeUserA] = makeHandoverStore('Store Alpha');
    [$storeB, $storeUserB] = makeHandoverStore('Store Beta');
    $colDp = makeHandoverDp('Col Driver');
    $finDp = makeHandoverDp('Fin Driver');
    $booking = makeHandoverBooking($storeA, $colDp, $finDp, true);
    $booking->update(['tip_amount_minor' => 501]); // 50/50 split: 250 collection, 251 final

    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $booking))
        ->assertOk();

    $handover = BookingStoreHandover::firstOrFail();

    // Store B cannot see or accept Store A's handover
    $this->actingAs($storeUserB, 'store')
        ->get(route('store.handovers.index'))
        ->assertOk()
        ->assertDontSee($handover->request_reference);

    $this->actingAs($storeUserB, 'store')
        ->postJson(route('store.handovers.accept', $handover))
        ->assertForbidden();

    // Store A sees it on Dashboard & Handovers, then accepts
    $this->actingAs($storeUserA, 'store')
        ->get(route('store.dashboard'))
        ->assertOk()
        ->assertSee('Pending Handover Request');

    $this->actingAs($storeUserA, 'store')
        ->postJson(route('store.handovers.accept', $handover))
        ->assertOk()
        ->assertJsonPath('status', BookingStoreHandover::STATUS_ACCEPTED);

    $handover->refresh();
    $booking->refresh();

    expect($handover->status)->toBe(BookingStoreHandover::STATUS_ACCEPTED);
    expect((int) $handover->accepted_by_store_user_id)->toBe((int) $storeUserA->id);
    expect(in_array(Booking::STATUS_STORE_RECEIVED, $booking->getCompletedOperationalStatuses(), true))->toBeTrue();
    expect($booking->booking_status)->toBe(Booking::STATUS_STORE_PROCESSING);
    expect($booking->currentCollectionAssignment->status)->toBe(DeliveryAssignment::STATUS_COMPLETED);
    expect($booking->currentCollectionAssignment->earnings_status)->toBe('credited');
    expect((int) $booking->currentCollectionAssignment->tip_share_minor)->toBe(250);
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'store_handover_accepted')->exists())->toBeTrue();

    // Collection Delivery Person ledger credited IMMEDIATELY (150 rate + 250 tip = 400); Store/Admin NOT yet credited
    expect(DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $colDp->id)->count())->toBe(1);
    expect((int) DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $colDp->id)->sum('amount_minor'))->toBe(400);
    expect(DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $finDp->id)->count())->toBe(0);
    expect(StoreAccount::count())->toBe(0);
    expect(AdminAccount::count())->toBe(0);

    // Duplicate acceptance blocked
    $this->actingAs($storeUserA, 'store')
        ->postJson(route('store.handovers.accept', $handover))
        ->assertStatus(422);

    // Delivery Person & Admin details both reflect completed handover & credited job
    $this->actingAs($colDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Store Handover Completed')
        ->assertSee('Collection Job Completed')
        ->assertSee($storeUserA->name);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee($handover->request_reference)
        ->assertSee($storeUserA->name);

    // Later when booking reaches final Completed, Collection Delivery Person is NOT double-credited,
    // while Final Delivery Person (150 + 251 = 401) and Store Account ARE credited.
    $stages = $booking->completed_operational_stages;
    $stages[Booking::STATUS_COMPLETED] = ['completed_at' => now()->toIso8601String()];
    $booking->update([
        'booking_status' => Booking::STATUS_COMPLETED,
        'completed_operational_stages' => $stages,
    ]);
    app(\App\Services\BookingFinancialSettlementService::class)->settleCompletedBooking($booking, $admin->id);

    expect(DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $colDp->id)->count())->toBe(1);
    expect(DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $finDp->id)->count())->toBe(1);
    expect((int) DB::table('delivery_person_ledger_entries')->where('delivery_person_id', $finDp->id)->sum('amount_minor'))->toBe(401);
    expect(StoreAccount::count())->toBe(1);
});

it('supports Store Report Issue without completing Store Received, allows resubmission, and cancels pending handover on store reassignment', function () {
    [$storeA, $storeUserA] = makeHandoverStore('Store One');
    [$storeB] = makeHandoverStore('Store Two');
    $colDp = makeHandoverDp('Col Driver');
    $booking = makeHandoverBooking($storeA, $colDp, null, true);

    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $booking))
        ->assertOk();

    $handover = BookingStoreHandover::firstOrFail();

    // Store A reports issue -> Store Received stays incomplete
    $this->actingAs($storeUserA, 'store')
        ->postJson(route('store.handovers.reject', $handover), [
            'reason' => '1 laundry bag missing at drop-off',
        ])
        ->assertOk()
        ->assertJsonPath('status', BookingStoreHandover::STATUS_REJECTED);

    $booking->refresh();
    expect(in_array(Booking::STATUS_STORE_RECEIVED, $booking->getCompletedOperationalStatuses(), true))->toBeFalse();

    // Delivery Person sees issue and can resubmit
    $this->actingAs($colDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertOk()
        ->assertSee('1 laundry bag missing at drop-off');

    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $booking))
        ->assertOk();

    $pending2 = BookingStoreHandover::where('booking_id', $booking->id)->where('status', 'pending')->firstOrFail();

    // Admin store reassignment cancels pending request
    app(StoreHandoverService::class)->cancelPendingForStoreChange($booking, $storeB->id, 1);
    $pending2->refresh();
    expect($pending2->status)->toBe(BookingStoreHandover::STATUS_CANCELLED);
});
