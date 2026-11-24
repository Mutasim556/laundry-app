<?php

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Models\BookingStorePickupRequest;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
});

function makeWorkflowAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Super Admin',
        'email' => 'admin' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    return $admin;
}

function makeWorkflowStore(string $name): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
        'store_code' => 'STR-' . random_int(1000, 9999),
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

function makeWorkflowDp(string $name, string $postcode = 'E1 0BJ'): DeliveryPerson
{
    $phone = '077007' . random_int(10000, 99999);
    $dp = DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'email' => strtolower(str_replace(' ', '.', $name)) . random_int(100, 999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '10 High St',
        'city' => 'London',
        'residential_postcode' => $postcode,
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026',
    ]);

    DeliveryPersonPostcode::create([
        'delivery_person_id' => $dp->id,
        'postcode' => $postcode,
        'postcode_compact' => strtoupper(str_replace(' ', '', $postcode)),
    ]);

    return $dp;
}

function makeStoreProcessingBooking(Store $store, DeliveryPerson $colDp, User $customer, string $gateway = 'stripe', string $paymentStatus = 'paid'): Booking
{
    $cat = ItemCategory::create([
        'name' => 'Dry Cleaning ' . random_int(100, 999),
        'slug' => 'dc-' . random_int(100, 999),
        'is_active' => true,
    ]);

    $svc = Service::create([
        'category_id' => $cat->id,
        'name' => 'Suit Press',
        'slug' => 'suit-press-' . random_int(100, 999),
        'pricing_mode' => 'fixed',
        'price_minor' => 2000,
        'is_active' => true,
    ]);

    $nowIso = now()->toIso8601String();
    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+44 7700900111',
        'booking_status' => Booking::STATUS_STORE_PROCESSING,
        'completed_operational_stages' => [
            Booking::STATUS_PROCESSING => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_ASSIGNED => ['completed_at' => $nowIso],
            Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => $nowIso],
            Booking::STATUS_FINALIZED => ['completed_at' => $nowIso],
            Booking::STATUS_DELIVERY_MAN_PICKED_UP => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_RECEIVED => ['completed_at' => $nowIso],
        ],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2600,
        'final_amount_minor' => 2600,
        'collection_delivery_fee_minor' => 600,
        'tip_amount_minor' => 200,
        'service_fee_minor' => 0,
        'discount_amount_minor' => 0,
        'payment_gateway' => $gateway,
        'payment_status' => $paymentStatus,
        'assigned_store_id' => $store->id,
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => [
            'full_address' => '221B Baker Street, London',
            'city' => 'London',
            'postcode' => 'E1 0BJ',
        ],
        'schedule_snapshot' => [
            'collection_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
        ],
        'version' => 1,
    ]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'category_id' => $cat->id,
        'category_name' => $cat->name,
        'service_id' => $svc->id,
        'service_name' => $svc->name,
        'pricing_mode' => 'fixed',
        'quantity' => 1,
        'catalog_unit_price_minor' => 2000,
        'actual_unit_price_minor' => 2000,
        'line_total_minor' => 2000,
        'is_removed' => false,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => $store->address_line_1,
        'is_current' => true,
        'assigned_at' => now(),
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-2026-' . random_int(100000, 999999),
        'booking_id' => $booking->id,
        'delivery_person_id' => $colDp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => '221B Baker Street, London',
        'destination_location' => $store->name,
        'rate_minor' => 300,
        'tip_share_minor' => 100,
        'currency' => 'GBP',
        'earnings_status' => 'credited',
        'assigned_at' => now()->subHour(),
        'completed_at' => now()->subMinutes(30),
    ]);

    return $booking;
}

test('full store complete to final delivery completion and single earnings credit flow', function () {
    $admin = makeWorkflowAdmin();
    [$store, $storeUser] = makeWorkflowStore('Mayfair Dry Cleaners');
    $colDp = makeWorkflowDp('Collection Driver');
    $finDp = makeWorkflowDp('Final Driver');
    $otherDp = makeWorkflowDp('Unrelated Driver');
    $customer = User::factory()->create();

    $booking = makeStoreProcessingBooking($store, $colDp, $customer, 'stripe', 'paid');

    // 1. Store marks Store Complete via AJAX
    $resComplete = $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $booking), ['note' => 'All garments pressed']);
    $resComplete->assertOk()->assertJsonPath('success', true)
        ->assertJsonPath('booking_status', Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP);

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP);
    expect(in_array(Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true))->toBeTrue();

    // Duplicate Store Complete is blocked
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $booking))
        ->assertStatus(422);

    // 2. Admin assigns Final Delivery Person (Collection assignment remains untouched)
    $resAssign = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $finDp->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ]);
    $resAssign->assertOk()->assertJsonPath('success', true);

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP);
    expect((int) $booking->currentCollectionAssignment->delivery_person_id)->toBe($colDp->id);
    expect((int) $booking->currentFinalDeliveryAssignment->delivery_person_id)->toBe($finDp->id);

    // 3. Store sends Store Pickup Request
    $resPickupReq = $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking), ['note' => 'Front counter']);
    $resPickupReq->assertOk()->assertJsonPath('success', true)
        ->assertJsonPath('pickup_request.status', BookingStorePickupRequest::STATUS_PENDING);

    $pickupReq = BookingStorePickupRequest::where('booking_id', $booking->id)->firstOrFail();

    // Duplicate active pickup request is blocked
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking))
        ->assertStatus(422);

    // Unrelated Delivery Person cannot view, accept, decline, collect, or deliver
    $this->actingAs($otherDp, 'delivery_person')
        ->postJson(route('delivery-person.pickup-requests.accept', $pickupReq))
        ->assertStatus(403);

    $this->actingAs($otherDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertStatus(403);

    // Assigned Delivery Person sees request on dashboard
    $this->actingAs($finDp, 'delivery_person')
        ->get(route('delivery-person.dashboard'))
        ->assertOk()
        ->assertSee($booking->reference)
        ->assertSee('Accept Request');

    // 4. Assigned Delivery Person accepts request (does NOT complete booking or credit earnings)
    $this->actingAs($finDp, 'delivery_person')
        ->postJson(route('delivery-person.pickup-requests.accept', $pickupReq))
        ->assertOk()
        ->assertJsonPath('pickup_request.status', BookingStorePickupRequest::STATUS_ACCEPTED);

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP);
    expect(DeliveryPersonLedgerEntry::where('delivery_person_id', $finDp->id)->count())->toBe(0);

    // 5. Delivery Person confirms physical collection from store -> On Delivery
    $this->actingAs($finDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.confirm-store-pickup', $booking), ['notes' => 'Picked up 2 bags'])
        ->assertOk()
        ->assertJsonPath('booking_status', Booking::STATUS_ON_DELIVERY);

    $booking->refresh();
    $pickupReq->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_ON_DELIVERY);
    expect($pickupReq->status)->toBe(BookingStorePickupRequest::STATUS_COLLECTED);
    expect($pickupReq->collected_at)->not->toBeNull();
    expect(DeliveryPersonLedgerEntry::where('delivery_person_id', $finDp->id)->count())->toBe(0);

    // 6. Delivery Person confirms final customer delivery -> Completed + exactly 1 earning credit
    $this->actingAs($finDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.confirm-delivered', $booking), ['proof_of_delivery_notes' => 'Handed to customer'])
        ->assertOk()
        ->assertJsonPath('booking_status', Booking::STATUS_COMPLETED);

    $booking->refresh();
    $pickupReq->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_COMPLETED);
    expect($pickupReq->status)->toBe(BookingStorePickupRequest::STATUS_COMPLETED);
    expect($pickupReq->delivered_at)->not->toBeNull();

    // Exactly 1 ledger entry for Final Delivery Person (300 delivery + 100 tip = 400 minor)
    $ledgerEntries = DeliveryPersonLedgerEntry::where('delivery_person_id', $finDp->id)->get();
    expect($ledgerEntries)->toHaveCount(1);
    expect((int) $ledgerEntries->first()->amount_minor)->toBe(400);

    // Duplicate delivery completion is rejected and does NOT double-credit
    $this->actingAs($finDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.confirm-delivered', $booking))
        ->assertStatus(422);
    expect(DeliveryPersonLedgerEntry::where('delivery_person_id', $finDp->id)->count())->toBe(1);

    // Customer My Orders displays Completed
    $this->actingAs($customer)
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('Completed');

    // Audit trail recorded key events
    $actions = BookingAuditLog::where('booking_id', $booking->id)->pluck('action')->all();
    expect($actions)->toContain(
        'store_complete_confirmed',
        'store_pickup_requested',
        'store_pickup_request_accepted',
        'store_pickup_collected',
        'final_delivery_completed'
    );
});

test('declined pickup request and admin reassignment invalidates stale request', function () {
    $admin = makeWorkflowAdmin();
    [$store, $storeUser] = makeWorkflowStore('Soho Laundry');
    $colDp = makeWorkflowDp('Col Driver');
    $finDp1 = makeWorkflowDp('Final Driver One');
    $finDp2 = makeWorkflowDp('Final Driver Two');
    $customer = User::factory()->create();

    $booking = makeStoreProcessingBooking($store, $colDp, $customer, 'cod', 'pending');

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $booking))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $finDp1->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ])->assertOk();

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking))
        ->assertOk();

    $req1 = BookingStorePickupRequest::where('booking_id', $booking->id)->firstOrFail();

    // Driver 1 declines with reason
    $this->actingAs($finDp1, 'delivery_person')
        ->postJson(route('delivery-person.pickup-requests.decline', $req1), [
            'decline_reason' => 'Vehicle breakdown',
        ])
        ->assertOk()
        ->assertJsonPath('pickup_request.status', BookingStorePickupRequest::STATUS_DECLINED);

    // Admin assigns Driver 2
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $finDp2->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ])->assertOk();

    // Stale declined request cannot be accepted
    $this->actingAs($finDp1, 'delivery_person')
        ->postJson(route('delivery-person.pickup-requests.accept', $req1))
        ->assertStatus(422);

    // Store sends new pickup request to Driver 2, then Admin reassigns before acceptance -> old pending request is cancelled
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking))
        ->assertOk();
    $req2 = BookingStorePickupRequest::where('booking_id', $booking->id)->latest('id')->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $finDp1->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
            'override_reason' => 'Closer to store',
        ])->assertOk();

    $req2->refresh();
    expect($req2->status)->toBe(BookingStorePickupRequest::STATUS_CANCELLED);

    // Driver 2 cannot accept cancelled stale request (422 or 403)
    $this->actingAs($finDp2, 'delivery_person')
        ->postJson(route('delivery-person.pickup-requests.accept', $req2))
        ->assertStatus(422);
});

test('cancelled booking and unassigned final delivery person cannot progress', function () {
    [$store, $storeUser] = makeWorkflowStore('Chelsea Cleaners');
    $colDp = makeWorkflowDp('Chelsea Col Driver');
    $customer = User::factory()->create();

    $booking = makeStoreProcessingBooking($store, $colDp, $customer, 'stripe', 'paid');

    // Cannot request pickup before Store Complete or Final Delivery Person assignment
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking))
        ->assertStatus(422);

    // Mark Store Complete, still cannot request pickup without Final Delivery Person
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $booking))
        ->assertOk();

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.pickup-request', $booking))
        ->assertStatus(422);

    // Cancelled booking cannot be marked Store Complete or requested
    $cancelledBooking = makeStoreProcessingBooking($store, $colDp, $customer, 'stripe', 'paid');
    $cancelledBooking->update([
        'booking_status' => Booking::STATUS_CANCELLED,
        'cancelled_at' => now(),
    ]);

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $cancelledBooking))
        ->assertStatus(422);
});
