<?php

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\User;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
});

function createStoreAssignmentBooking(string $postcode = 'M1 2PF', ?User $user = null): Booking
{
    return Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $user?->id,
        'customer_name' => 'Test Customer',
        'customer_email' => $user?->email ?? 'customer@example.com',
        'customer_phone' => '+447700900111',
        'customer_type' => $user ? 'registered' : 'guest',
        'address_snapshot' => [
            'address_line_1' => '18 London Road',
            'city' => 'Manchester',
            'postcode' => $postcode,
        ],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => Booking::STATUS_STORE_ASSIGNED,
        'completed_operational_stages' => [
            Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        ],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2500,
    ]);
}

test('case 1: cannot complete Store Assigned stage without an active assigned store', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $booking = createStoreAssignmentBooking('M1 2PF');

    $page = $this->actingAs($admin, 'admin')->get(route('admin.bookings.show', $booking));
    $page->assertStatus(200)
        ->assertSee('Store: Not Assigned')
        ->assertSee('Please assign a store before completing this stage.');

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking), [
        'expected_stage' => Booking::STATUS_STORE_ASSIGNED,
        'version' => $booking->version,
    ])->assertSessionHasErrors(['booking_status']);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.update', $booking), [
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
    ])->assertSessionHasErrors(['booking_status']);

    expect($booking->fresh()->booking_status)->toBe(Booking::STATUS_STORE_ASSIGNED);
});

test('case 2: assigning matching active store saves snapshot and enables advancing to Delivery Man Assigned', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $booking = createStoreAssignmentBooking('M1 2PF');

    $matchingStore = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Manchester Central Store',
        'store_code' => 'MCR-01',
        'slug' => 'manchester-central-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960100',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '123 Example Road',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'full_address' => '123 Example Road, Manchester, M1 2PF',
    ]);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $matchingStore->id,
    ])->assertSessionHasNoErrors();

    $booking->refresh();
    expect($booking->assigned_store_id)->toBe($matchingStore->id);
    expect($booking->booking_status)->toBe(Booking::STATUS_STORE_ASSIGNED);

    $assignment = $booking->currentStoreAssignment;
    expect($assignment)->not->toBeNull();
    expect($assignment->postcode_matched)->toBeTrue();
    expect($assignment->store_name_snapshot)->toBe('Manchester Central Store');
    expect($assignment->store_postcode_snapshot)->toBe('M1 2PF');

    // Now advancing succeeds to Delivery Man Assigned
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking), [
        'expected_stage' => Booking::STATUS_STORE_ASSIGNED,
        'version' => $booking->version,
    ])->assertSessionHasNoErrors();

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_ASSIGNED);
    expect(array_key_exists(Booking::STATUS_STORE_ASSIGNED, $booking->completed_operational_stages ?? []))->toBeTrue();
});

test('case 3: manual fallback requires override reason when postcode does not match and rejects inactive stores', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $booking = createStoreAssignmentBooking('M99 9ZZ');

    $otherStore = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Piccadilly Laundry Hub',
        'store_code' => 'PIC-01',
        'slug' => 'piccadilly-laundry-hub',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960200',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '20 Piccadilly',
        'city' => 'Manchester',
        'postcode' => 'M1 1AE',
        'full_address' => '20 Piccadilly, Manchester, M1 1AE',
    ]);

    $inactiveStore = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'suspended',
        'name' => 'Inactive Branch',
        'store_code' => 'INA-01',
        'slug' => 'inactive-branch',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960300',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '99 Closed St',
        'city' => 'Manchester',
        'postcode' => 'M99 9ZZ',
        'full_address' => '99 Closed St, Manchester, M99 9ZZ',
    ]);

    // Inactive store rejected
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $inactiveStore->id,
    ])->assertSessionHasErrors(['store_id']);

    // Non-matching store without override_reason rejected
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $otherStore->id,
    ])->assertSessionHasErrors(['override_reason']);

    // Non-matching store with override_reason succeeds
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $otherStore->id,
        'override_reason' => 'Closest operational hub covering overflow.',
    ])->assertSessionHasNoErrors();

    $assignment = $booking->fresh()->currentStoreAssignment;
    expect($assignment->postcode_matched)->toBeFalse();
    expect($assignment->override_reason)->toBe('Closest operational hub covering overflow.');
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'store_assigned')->exists())->toBeTrue();
});

test('case 4 & 5: customer order view displays assigned store safely and reassignment preserves history and blocks active delivery conflict', function () {
    $user = User::factory()->create();
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $booking = createStoreAssignmentBooking('M1 1AE', $user);

    // Before assignment: customer sees pending notice
    $this->actingAs($user, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertStatus(200)
        ->assertSee('Assigned Laundry Store')
        ->assertSee('Store assignment pending');

    $store1 = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'First UK Store',
        'store_code' => 'UK-01',
        'slug' => 'first-uk-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960401',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '10 First Road',
        'city' => 'Manchester',
        'postcode' => 'M1 1AE',
        'full_address' => '10 First Road, Manchester, M1 1AE',
    ]);

    $store2 = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Second UK Store',
        'store_code' => 'UK-02',
        'slug' => 'second-uk-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960402',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '20 Second Road',
        'city' => 'London',
        'postcode' => 'E1 0BJ',
        'full_address' => '20 Second Road, London, E1 0BJ',
    ]);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $store1->id,
    ])->assertSessionHasNoErrors();

    // Customer sees assigned store details
    $this->actingAs($user, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertStatus(200)
        ->assertSee('First UK Store')
        ->assertSee('10 First Road')
        ->assertSee('M1 1AE');

    // Reassign to store2
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $store2->id,
        'override_reason' => 'Capacity balancing across branches',
    ])->assertSessionHasNoErrors();

    expect(BookingStoreAssignment::where('booking_id', $booking->id)->count())->toBe(2);
    expect(BookingStoreAssignment::where('booking_id', $booking->id)->where('is_current', true)->first()->store_id)->toBe($store2->id);
    expect(BookingStoreAssignment::where('booking_id', $booking->id)->where('status', 'reassigned')->count())->toBe(1);

    // Customer sees updated store2 and does NOT see internal override reason
    $this->actingAs($user, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertStatus(200)
        ->assertSee('Second UK Store')
        ->assertDontSee('Capacity balancing across branches');

    // Block reassignment once delivery assignment is picked_up
    $courier = DeliveryPerson::create([
        'code' => 'DP-9001',
        'full_name' => 'Test Courier',
        'email' => 'courier9001@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900901',
        'date_of_birth' => '1990-01-01',
        'address_line_1' => '1 Courier Way',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 1AE',
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-2026-990001',
        'booking_id' => $booking->id,
        'delivery_person_id' => $courier->id,
        'store_id' => $store2->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_PICKED_UP,
        'pickup_location' => '18 London Road',
        'destination_location' => 'Second UK Store',
        'assigned_at' => now(),
        'picked_up_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.assign-store', $booking), [
        'store_id' => $store1->id,
        'override_reason' => 'Should be blocked',
    ])->assertSessionHasErrors(['store_id']);
});


test('ajax store search prioritizes exact postcode matches and ajax assign returns structured json idempotently', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $booking = createStoreAssignmentBooking('M1 2PF');

    $otherStore = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Alpha Other Store',
        'store_code' => 'OTH-01',
        'slug' => 'alpha-other-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960501',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '10 Other Road',
        'city' => 'London',
        'postcode' => 'E1 0BJ',
        'full_address' => '10 Other Road, London, E1 0BJ',
    ]);

    $matchingStore = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Zeta Matching Store',
        'store_code' => 'MAT-01',
        'slug' => 'zeta-matching-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960502',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '20 Match Street',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'full_address' => '20 Match Street, Manchester, M1 2PF',
    ]);

    Store::create([
        'ownership_type' => 'my_store',
        'status' => 'suspended',
        'name' => 'Suspended Hidden Store',
        'store_code' => 'SUS-01',
        'slug' => 'suspended-hidden-store',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '1614960503',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '30 Closed Road',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'full_address' => '30 Closed Road, Manchester, M1 2PF',
    ]);

    $searchRes = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.stores.search', $booking));

    $searchRes->assertStatus(200);
    $results = $searchRes->json('results');
    expect($results[0]['id'])->toBe($matchingStore->id);
    expect($results[0]['postcode_match'])->toBeTrue();
    expect(collect($results)->pluck('store_name')->all())->not->toContain('Suspended Hidden Store');

    // AJAX save assignment
    $saveRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-store', $booking), [
            'store_id' => $matchingStore->id,
        ]);

    $saveRes->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('booking_id', $booking->id)
        ->assertJsonPath('store_id', $matchingStore->id)
        ->assertJsonPath('store_name', 'Zeta Matching Store')
        ->assertJsonPath('store_postcode', 'M1 2PF')
        ->assertJsonPath('postcode_match', true)
        ->assertJsonPath('can_complete_store_stage', true);

    // Repeated click / duplicate request does not create duplicate assignment row
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-store', $booking), [
            'store_id' => $matchingStore->id,
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(BookingStoreAssignment::where('booking_id', $booking->id)->count())->toBe(1);
});
