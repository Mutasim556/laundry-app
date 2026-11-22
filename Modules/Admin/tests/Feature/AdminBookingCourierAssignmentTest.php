<?php

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\User;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Database\Seeders\UkDemoDeliveryPersonsSeeder;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryAssignmentEvent;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
});

function createCourierTestBooking(string $status = Booking::STATUS_DELIVERY_MAN_ASSIGNED, string $postcode = 'M1 2PF', ?User $user = null): Booking
{
    $store = Store::create([
        'name' => 'Manchester Central Store',
        'slug' => 'manchester-central-store-' . random_int(1000, 9999),
        'code' => 'STR-' . random_int(1000, 9999),
        'email' => 'store' . random_int(1000, 9999) . '@example.com',
        'phone' => '+441614960001',
        'address_line_1' => '10 Piccadilly',
        'city' => 'Manchester',
        'postcode' => $postcode,
        'country_code' => 'GB',
        'status' => 'active',
    ]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $user?->id,
        'customer_name' => 'Courier Test Customer',
        'customer_email' => $user?->email ?? 'courier-customer@example.com',
        'customer_phone' => '+447700900111',
        'customer_type' => $user ? 'registered' : 'guest',
        'address_snapshot' => [
            'address_line_1' => '22 Oxford Street',
            'city' => 'Manchester',
            'postcode' => $postcode,
        ],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $status,
        'assigned_store_id' => $store->id,
        'completed_operational_stages' => [
            Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
            Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        ],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2500,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '10 Piccadilly, Manchester',
        'store_city_snapshot' => 'Manchester',
        'store_postcode_snapshot' => $postcode,
        'store_phone_snapshot' => $store->phone,
        'customer_postcode_snapshot' => $postcode,
        'postcode_matched' => true,
        'is_current' => true,
        'assigned_at' => now(),
    ]);

    return $booking;
}

function createCourierPerson(string $code, string $name, ?string $coveragePostcode = null, string $workStatus = 'active'): DeliveryPerson
{
    $person = DeliveryPerson::create([
        'code' => $code,
        'full_name' => $name,
        'email' => strtolower($code) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900' . random_int(100, 999),
        'date_of_birth' => '1992-05-10',
        'address_line_1' => '99 Private Residential Lane',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 1AE',
        'employment_type' => 'employee',
        'work_status' => $workStatus,
        'availability_status' => 'available',
        'payment_calculation_type' => 'separate_rates',
        'per_collection_rate_minor' => 400,
        'per_delivery_rate_minor' => 450,
        'currency' => 'GBP',
    ]);

    if ($coveragePostcode !== null) {
        DeliveryPersonPostcode::create([
            'delivery_person_id' => $person->id,
            'postcode' => strtoupper($coveragePostcode),
            'postcode_compact' => UkAddress::compactPostcode($coveragePostcode),
        ]);
    }

    return $person;
}

test('cannot complete Delivery Man Assigned or Store Pickup stages without required courier assignment', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED, 'M1 2PF');

    $this->actingAs($admin, 'admin')->get(route('admin.bookings.show', $booking))
        ->assertStatus(200)
        ->assertSee('Delivery Person: Not Assigned')
        ->assertSee('Store & Delivery Assignments', false);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $booking), [
        'expected_stage' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'version' => $booking->version,
    ])->assertSessionHasErrors(['booking_status']);

    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.update', $booking), [
        'booking_status' => Booking::STATUS_FINALIZED,
    ])->assertSessionHasErrors(['booking_status']);

    $pickupBooking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, 'M1 2PF');
    $this->actingAs($admin, 'admin')->post(route('admin.bookings.status.advance', $pickupBooking), [
        'expected_stage' => Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
        'version' => $pickupBooking->version,
    ])->assertSessionHasErrors(['booking_status']);
});

test('AJAX courier search prioritizes postcode-matching couriers and filters inactive couriers', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED, 'M1 2PF');
    $matching = createCourierPerson('DP-MATCH-1', 'Oliver Match', 'M1 2PF');
    $other = createCourierPerson('DP-OTHER-1', 'Amelia Other', 'E1 0BJ');
    createCourierPerson('DP-INACTIVE', 'Inactive Courier', 'M1 2PF', 'inactive');

    $res = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.couriers.search', $booking))
        ->assertStatus(200)
        ->assertJsonPath('has_postcode_match', true);

    $ids = array_column($res->json('results'), 'id');
    expect($ids[0])->toBe($matching->id)
        ->and($ids)->toContain($other->id)
        ->and($ids)->not->toContain(DeliveryPerson::where('code', 'DP-INACTIVE')->value('id'));
});

test('AJAX courier assignment requires override reason on mismatch, saves assignment, unlocks stage, and hides inline controls after done', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED, 'M1 2PF');
    $mismatchCourier = createCourierPerson('DP-MISMATCH', 'Liam Fallback', 'E1 0BJ');

    // Missing override reason -> 422
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $mismatchCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['override_reason']);

    // With override reason -> 200
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $mismatchCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'override_reason' => 'Nearest available driver on duty',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('can_complete_stage', true)
        ->assertJsonPath('courier_name', 'Liam Fallback');

    expect($booking->fresh()->hasValidCollectionCourierAssignment())->toBeTrue();
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'courier_assigned')->exists())->toBeTrue();

    // Now advancing past Delivery Man Assigned succeeds
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking->fresh()), [
            'expected_stage' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
            'version' => $booking->fresh()->version,
        ])
        ->assertSessionHas('success');

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_FINALIZED);

    // Inline store & courier prompts are hidden on Finalized, while persistent Store & Delivery Assignments section remains
    $this->actingAs($admin, 'admin')->get(route('admin.bookings.show', $booking))
        ->assertStatus(200)
        ->assertDontSee('id="inlineStoreAssignmentBox"', false)
        ->assertDontSee('id="inlineCollectionCourierBox"', false)
        ->assertSee('id="storeDeliveryAssignmentsSection"', false)
        ->assertSee('Liam Fallback');
});

test('reassignment before picked_up supersedes previous assignment with audit log and blocks after picked_up', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED, 'M1 2PF');
    $firstCourier = createCourierPerson('DP-FIRST', 'First Driver', 'M1 2PF');
    $secondCourier = createCourierPerson('DP-SECOND', 'Second Driver', 'M1 2PF');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $firstCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        ])
        ->assertStatus(200);

    $firstAssignmentId = DeliveryAssignment::where('booking_id', $booking->id)->value('id');

    // Reassigning requires reason
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $secondCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        ])
        ->assertStatus(422);

    // Reassigning with reason supersedes first assignment
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $secondCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'override_reason' => 'First driver vehicle issue',
        ])
        ->assertStatus(200)
        ->assertJsonPath('courier_name', 'Second Driver');

    $firstAssignment = DeliveryAssignment::find($firstAssignmentId);
    expect($firstAssignment->status)->toBe(DeliveryAssignment::STATUS_CANCELLED)
        ->and($firstAssignment->earnings_status)->toBe('not_payable')
        ->and(DeliveryAssignmentEvent::where('delivery_assignment_id', $firstAssignmentId)->where('event_type', 'reassigned_superseded')->exists())->toBeTrue();

    // Mark second assignment picked_up -> reassignment must now be blocked
    $secondAssignment = $booking->fresh()->currentCollectionAssignment;
    $secondAssignment->update(['status' => DeliveryAssignment::STATUS_PICKED_UP, 'picked_up_at' => now()]);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $firstCourier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'override_reason' => 'Should be blocked',
        ])
        ->assertStatus(422);
});

test('customer My Orders displays public collection courier details without exposing private info', function () {
    $user = User::factory()->create();
    $booking = createCourierTestBooking(Booking::STATUS_DELIVERY_MAN_ASSIGNED, 'M1 2PF', $user);
    $courier = createCourierPerson('DP-PUB', 'Oliver Public', 'M1 2PF');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.assign-courier', $booking), [
            'delivery_person_id' => $courier->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        ])
        ->assertStatus(200);

    $this->actingAs($user)
        ->get(route('orders.show', $booking->reference))
        ->assertStatus(200)
        ->assertSee('Collection Delivery Person')
        ->assertSee('Oliver Public')
        ->assertSee($courier->phone_display)
        ->assertDontSee('99 Private Residential Lane')
        ->assertDontSee('1992-05-10');
});

test('UkDemoDeliveryPersonsSeeder is idempotent in local environment', function () {
    UkAddress::create([
        'postcode' => 'M1 2PF',
        'postcode_compact' => 'M12PF',
        'address_line_1' => '18 London Road',
        'city' => 'Manchester',
        'county' => 'Greater Manchester',
        'is_active' => true,
    ]);

    app()->detectEnvironment(fn () => 'local');
    $seeder = new UkDemoDeliveryPersonsSeeder();
    $seeder->run();
    $countAfterFirst = DeliveryPerson::count();
    $seeder->run();
    $countAfterSecond = DeliveryPerson::count();

    expect($countAfterFirst)->toBeGreaterThanOrEqual(3)
        ->and($countAfterSecond)->toBe($countAfterFirst);
});
