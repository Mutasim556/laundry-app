<?php

use App\Models\Booking;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function makeJobTestStore(): Store
{
    return Store::create([
        'name' => 'Piccadilly Cleaners',
        'slug' => 'piccadilly-' . random_int(1000, 9999),
        'store_code' => 'PIC-' . random_int(1000, 9999),
        'email' => 'pic' . random_int(1000, 9999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '23 Crowder Street',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);
}

function makeJobTestDp(string $name): DeliveryPerson
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

function makeJobTestBooking(Store $store, User $customer): Booking
{
    $nowIso = now()->toIso8601String();
    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+44 7700900111',
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
        'completed_operational_stages' => [
            Booking::STATUS_PROCESSING => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_ASSIGNED => ['completed_at' => $nowIso],
            Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => $nowIso],
            Booking::STATUS_FINALIZED => ['completed_at' => $nowIso],
            Booking::STATUS_DELIVERY_MAN_PICKED_UP => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_RECEIVED => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_PROCESSING => ['completed_at' => $nowIso],
            Booking::STATUS_STORE_COMPLETE => ['completed_at' => $nowIso],
        ],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2600,
        'final_amount_minor' => 2600,
        'collection_delivery_fee_minor' => 1200,
        'tip_amount_minor' => 0,
        'service_fee_minor' => 0,
        'discount_amount_minor' => 0,
        'payment_gateway' => 'stripe',
        'payment_status' => 'paid',
        'assigned_store_id' => $store->id,
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => [
            'full_address' => '221B Baker Street, Manchester',
            'city' => 'Manchester',
            'postcode' => 'M1 2PF',
        ],
        'schedule_snapshot' => [
            'collection_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
        ],
        'version' => 1,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => $store->address_line_1,
        'is_current' => true,
        'assigned_at' => now(),
    ]);

    return $booking;
}

test('same delivery person assigned to both legs sees two independent job cards and separate detail pages', function () {
    $store = makeJobTestStore();
    $dp = makeJobTestDp('Amelia Clarke');
    $otherDp = makeJobTestDp('Other Driver');
    $customer = User::factory()->create();
    $booking = makeJobTestBooking($store, $customer);

    $colAsg = DeliveryAssignment::create([
        'assignment_reference' => 'ASG-2026-000101',
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => '221B Baker Street, Manchester',
        'pickup_postcode' => 'M1 2PF',
        'destination_location' => $store->name,
        'rate_minor' => 500,
        'tip_share_minor' => 0,
        'currency' => 'GBP',
        'earnings_status' => 'credited',
        'assigned_at' => now()->subHours(2),
        'completed_at' => now()->subHour(),
    ]);

    $finAsg = DeliveryAssignment::create([
        'assignment_reference' => 'ASG-2026-000102',
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_location' => $store->name,
        'destination_location' => '221B Baker Street, Manchester',
        'destination_postcode' => 'M1 2PF',
        'rate_minor' => 700,
        'tip_share_minor' => 0,
        'currency' => 'GBP',
        'earnings_status' => 'pending',
        'assigned_at' => now(),
    ]);

    // 1. My Bookings renders TWO independent job cards for the same booking
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.bookings.index'))
        ->assertOk()
        ->assertSee($colAsg->job_code)
        ->assertSee($finAsg->job_code)
        ->assertSee('View Collection Job')
        ->assertSee('View Final Delivery Job')
        ->assertSee('GBP 5.00')
        ->assertSee('GBP 7.00')
        ->assertSee('Awaiting Pickup Request');

    // 2. Collections page shows ONLY Collection job
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.collections.index'))
        ->assertOk()
        ->assertSee($colAsg->job_code)
        ->assertDontSee($finAsg->job_code);

    // 3. Final Deliveries page shows ONLY Final Delivery job
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.final-deliveries.index'))
        ->assertOk()
        ->assertSee($finAsg->job_code)
        ->assertDontSee($colAsg->job_code);

    // 4. Separate job detail routes open their own independent contexts
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.collection-jobs.show', $colAsg))
        ->assertOk()
        ->assertSee('COLLECTION JOB')
        ->assertSee($colAsg->job_code);

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.final-delivery-jobs.show', $finAsg))
        ->assertOk()
        ->assertSee('Final Delivery Assignment')
        ->assertSee($finAsg->job_code);

    // 5. Cross-job type and cross-person authorization enforced
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.collection-jobs.show', $finAsg))
        ->assertStatus(403);

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.final-delivery-jobs.show', $colAsg))
        ->assertStatus(403);

    $this->actingAs($otherDp, 'delivery_person')
        ->get(route('delivery-person.collection-jobs.show', $colAsg))
        ->assertStatus(403);

    $this->actingAs($otherDp, 'delivery_person')
        ->get(route('delivery-person.final-delivery-jobs.show', $finAsg))
        ->assertStatus(403);
});
