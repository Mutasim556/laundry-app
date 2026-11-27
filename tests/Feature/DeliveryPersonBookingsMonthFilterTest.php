<?php

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function makeDpForBookings(string $suffix = '1'): DeliveryPerson
{
    $phone = '0770090011' . $suffix;

    return DeliveryPerson::create([
        'full_name' => 'Driver ' . $suffix,
        'date_of_birth' => '1990-01-01',
        'email' => "dp{$suffix}@example.com",
        'password' => bcrypt('Secret123!'),
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '10 High St',
        'city' => 'London',
        'residential_postcode' => 'SW1A 1AA',
        'code' => 'DP-' . $suffix,
        'work_status' => 'active',
        'availability_status' => 'available',
        'portal_access_enabled' => true,
        'currency' => 'GBP',
    ]);
}

function makeDpBooking(string $ref, string $customerName = 'Alice Customer', string $postcode = 'SW1A 1AA'): Booking
{
    return Booking::create([
        'reference' => $ref,
        'customer_name' => $customerName,
        'customer_email' => strtolower($ref) . '@example.com',
        'customer_phone' => '07700900999',
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'payment_status' => 'paid',
        'payment_method' => 'cod',
        'currency' => 'GBP',
        'subtotal' => 20.00,
        'total_amount' => 20.00,
        'address_snapshot' => [
            'full_address' => '10 Downing Street, London',
            'postcode' => $postcode,
        ],
        'schedule_snapshot' => [],
        'services_snapshot' => [],
    ]);
}

function makeDpAssignment(array $overrides): DeliveryAssignment
{
    return DeliveryAssignment::create(array_merge([
        'pickup_location' => '10 Downing Street, London',
        'destination_location' => 'Central Store, London',
        'rate_minor' => 500,
        'currency' => 'GBP',
    ], $overrides));
}

test('my bookings defaults to current month, separates collection and final delivery, and enforces ownership', function () {
    $dp1 = makeDpForBookings('101');
    $dp2 = makeDpForBookings('102');
    $store = Store::create(['name' => 'Central Store', 'slug' => 'central-store-' . random_int(100, 999), 'store_code' => 'CS-' . random_int(100, 999), 'email' => 'cs@example.com', 'phone' => '02079460001', 'password' => bcrypt('Secret123!'), 'status' => true]);

    $now = now();
    $prevMonth = $now->copy()->subMonthNoOverflow();

    $booking = makeDpBooking('LND-CUR-001', 'Current Customer', 'E1 6AN');
    $prevBooking = makeDpBooking('LND-PRV-002', 'Previous Customer', 'W1A 1AA');
    $otherBooking = makeDpBooking('LND-OTH-003', 'Other Driver Customer', 'N1 9GU');

    // Two independent jobs for the same parent booking in current month
    makeDpAssignment([
        'assignment_reference' => 'COL-1001',
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp1->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_postcode' => 'E1 6AN',
        'scheduled_at' => $now->copy()->startOfMonth()->addDays(2),
        'assigned_at' => $now,
    ]);

    makeDpAssignment([
        'assignment_reference' => 'DEL-1002',
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp1->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'destination_postcode' => 'E1 6AN',
        'scheduled_at' => $now->copy()->startOfMonth()->addDays(3),
        'assigned_at' => $now,
        'rate_minor' => 600,
    ]);

    // Previous month job
    makeDpAssignment([
        'assignment_reference' => 'COL-0900',
        'booking_id' => $prevBooking->id,
        'delivery_person_id' => $dp1->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_postcode' => 'W1A 1AA',
        'scheduled_at' => $prevMonth->copy()->startOfMonth()->addDays(4),
        'assigned_at' => $prevMonth,
    ]);

    // Another driver's job in current month
    makeDpAssignment([
        'assignment_reference' => 'COL-9999',
        'booking_id' => $otherBooking->id,
        'delivery_person_id' => $dp2->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_postcode' => 'N1 9GU',
        'scheduled_at' => $now,
        'assigned_at' => $now,
    ]);

    $this->actingAs($dp1, 'delivery_person')
        ->get(route('delivery-person.bookings.index'))
        ->assertOk()
        ->assertSee($now->format('F Y'))
        ->assertSee('LND-CUR-001')
        ->assertSee('View Collection Job')
        ->assertSee('View Final Delivery Job')
        ->assertDontSee('LND-PRV-002')
        ->assertDontSee('LND-OTH-003');
});

test('ajax month switching, year boundaries, filters, search, sorting, and pagination work', function () {
    $dp = makeDpForBookings('201');
    $store = Store::create(['name' => 'East Store', 'slug' => 'east-store-' . random_int(100, 999), 'store_code' => 'ES-' . random_int(100, 999), 'email' => 'es@example.com', 'phone' => '02079460002', 'password' => bcrypt('Secret123!'), 'status' => true]);

    // December 2025 & January 2026 boundary jobs
    $decBooking = makeDpBooking('LND-DEC-001', 'December User', 'EC1A 1BB');
    $janBooking = makeDpBooking('LND-JAN-001', 'January User', 'SW1A 2AA');

    makeDpAssignment([
        'assignment_reference' => 'COL-DEC1',
        'booking_id' => $decBooking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_postcode' => 'EC1A 1BB',
        'scheduled_at' => '2025-12-15 10:00:00',
        'assigned_at' => '2025-12-14 09:00:00',
    ]);

    makeDpAssignment([
        'assignment_reference' => 'DEL-JAN1',
        'booking_id' => $janBooking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'destination_postcode' => 'SW1A 2AA',
        'scheduled_at' => '2026-01-10 14:00:00',
        'assigned_at' => '2026-01-09 10:00:00',
        'rate_minor' => 700,
    ]);

    // AJAX request for Dec 2025 -> next_month should be 1, next_year 2026
    $decRes = $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.bookings.index', ['month' => 12, 'year' => 2025]))
        ->assertOk()
        ->assertJsonPath('month_label', 'December 2025')
        ->assertJsonPath('next_month', 1)
        ->assertJsonPath('next_year', 2026)
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.collection', 1);

    expect($decRes->json('results_html'))->toContain('LND-DEC-001')->not->toContain('LND-JAN-001');

    // AJAX request for Jan 2026 -> prev_month should be 12, prev_year 2025
    $janRes = $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.bookings.index', ['month' => 1, 'year' => 2026]))
        ->assertOk()
        ->assertJsonPath('month_label', 'January 2026')
        ->assertJsonPath('prev_month', 12)
        ->assertJsonPath('prev_year', 2025)
        ->assertJsonPath('summary.final_delivery', 1);

    expect($janRes->json('results_html'))->toContain('LND-JAN-001');

    // Empty month shows empty state message
    $emptyRes = $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.bookings.index', ['month' => 5, 'year' => 2024]))
        ->assertOk()
        ->assertJsonPath('summary.total', 0);

    expect($emptyRes->json('results_html'))->toContain('No booking jobs found for the selected month and filters.');

    // Pagination (14 jobs in Feb 2026 -> 12 on page 1, 2 on page 2) + search/sort
    for ($i = 1; $i <= 14; $i++) {
        $b = makeDpBooking('LND-FEB-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), "Feb User {$i}", 'SE1 7PB');
        makeDpAssignment([
            'assignment_reference' => 'COL-FEB' . $i,
            'booking_id' => $b->id,
            'delivery_person_id' => $dp->id,
            'store_id' => $store->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'pickup_postcode' => 'SE1 7PB',
            'scheduled_at' => sprintf('2026-02-%02d 10:00:00', $i),
            'assigned_at' => '2026-02-01 09:00:00',
        ]);
    }

    $page2 = $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.bookings.index', [
            'month' => 2,
            'year' => 2026,
            'job_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'sort' => 'scheduled_asc',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('summary.total', 14);

    expect($page2->json('results_html'))->toContain('LND-FEB-013')->toContain('LND-FEB-014')->not->toContain('LND-FEB-001');

    // Search by postcode within Feb 2026
    $searchRes = $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.bookings.index', [
            'month' => 2,
            'year' => 2026,
            'search' => 'LND-FEB-007',
        ]))
        ->assertOk()
        ->assertJsonPath('summary.total', 1);

    expect($searchRes->json('results_html'))->toContain('LND-FEB-007');
});

test('cash account and settlements default to current month and support ajax month filters', function () {
    $dp = makeDpForBookings('301');

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.cash-account'))
        ->assertOk()
        ->assertSee(now()->format('F Y'));

    $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.cash-account', ['month' => 12, 'year' => 2025]))
        ->assertOk()
        ->assertJsonPath('month_label', 'December 2025')
        ->assertJsonPath('next_month', 1)
        ->assertJsonPath('next_year', 2026);

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.settlements'))
        ->assertOk()
        ->assertSee(now()->format('F Y'));

    $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.settlements', ['month' => 1, 'year' => 2026]))
        ->assertOk()
        ->assertJsonPath('month_label', 'January 2026')
        ->assertJsonPath('prev_month', 12)
        ->assertJsonPath('prev_year', 2025);
});
