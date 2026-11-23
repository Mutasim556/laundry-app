<?php

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCustomerOrder(
    ?User $user,
    string $email,
    string $status,
    array $completedStages = [],
    ?string $createdAt = null,
    ?string $reference = null
): Booking {
    $booking = Booking::create([
        'reference' => $reference ?? ('LND-2026-' . random_int(100000, 999999)),
        'user_id' => $user?->id,
        'customer_name' => $user?->name ?? 'Guest Customer',
        'customer_email' => $email,
        'customer_phone' => '+447700900111',
        'customer_type' => $user ? 'registered' : 'guest',
        'address_snapshot' => ['full_address' => '10 Oxford Road, Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23', 'collection_slot' => '10:00 - 12:00', 'delivery_date' => '2026-11-24', 'delivery_slot' => '14:00 - 16:00'],
        'services_snapshot' => [['name' => 'Mixed Wash & Fold']],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $status,
        'completed_operational_stages' => $completedStages,
        'currency' => 'GBP',
        'estimated_amount_minor' => 2500,
        'confirmed_at' => now(),
    ]);

    if ($createdAt !== null) {
        $booking->forceFill(['created_at' => $createdAt])->saveQuietly();
    }

    return $booking;
}

test('redirects guests to login and isolates bookings per authenticated customer without Link Order UI', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));

    $customerA = User::factory()->create(['email' => 'alice@example.com', 'email_verified_at' => now()]);
    $customerB = User::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

    $orderA = makeCustomerOrder($customerA, 'alice@example.com', Booking::STATUS_PROCESSING, [], null, 'LND-2026-111111');
    $orderB = makeCustomerOrder($customerB, 'bob@example.com', Booking::STATUS_PROCESSING, [], null, 'LND-2026-222222');

    $this->actingAs($customerA)
        ->get(route('orders.index'))
        ->assertStatus(200)
        ->assertSee('LND-2026-111111')
        ->assertDontSee('LND-2026-222222')
        ->assertDontSee('Link Order');

    $this->actingAs($customerB)
        ->get(route('orders.index'))
        ->assertStatus(200)
        ->assertSee('LND-2026-222222')
        ->assertDontSee('LND-2026-111111');

    $this->actingAs($customerB)
        ->get(route('orders.show', $orderA->reference))
        ->assertStatus(403);
});

test('automatically associates historical unclaimed confirmed bookings for email-verified customer', function () {
    $customer = User::factory()->create(['email' => 'legacy@example.com', 'email_verified_at' => now()]);
    $legacyOrder = makeCustomerOrder(null, 'legacy@example.com', Booking::STATUS_STORE_ASSIGNED, [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
    ], null, 'LND-2026-333333');

    $this->actingAs($customer)
        ->get(route('orders.index'))
        ->assertStatus(200)
        ->assertSee('LND-2026-333333');

    expect($legacyOrder->fresh()->user_id)->toBe($customer->id);
});

test('computes mathematically consistent statistics and applies date, status, search, and pagination filters', function () {
    $customer = User::factory()->create(['email' => 'stats@example.com', 'email_verified_at' => now()]);

    // 1 Pending
    makeCustomerOrder($customer, 'stats@example.com', Booking::STATUS_PROCESSING, [], '2026-11-05 10:00:00', 'LND-2026-000001');
    // 1 In Progress
    $inProgress = makeCustomerOrder($customer, 'stats@example.com', Booking::STATUS_STORE_PROCESSING, [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
    ], '2026-11-12 10:00:00', 'LND-2026-000002');
    // 1 Completed with invoice
    $completed = makeCustomerOrder($customer, 'stats@example.com', Booking::STATUS_COMPLETED, [
        Booking::STATUS_COMPLETED => ['completed_at' => now()->toIso8601String()],
    ], '2026-11-20 10:00:00', 'LND-2026-000003');
    BookingInvoice::create([
        'booking_id' => $completed->id,
        'invoice_number' => 'INV-2026-000999',
        'revision_number' => 1,
        'is_current' => true,
        'currency' => 'GBP',
        'business_snapshot' => [],
        'customer_snapshot' => [],
        'items_snapshot' => [],
        'snapshot_hash' => hash('sha256', 'test'),
        'issued_at' => now(),
    ]);
    // 1 Cancelled
    makeCustomerOrder($customer, 'stats@example.com', Booking::STATUS_CANCELLED, [], '2026-11-22 10:00:00', 'LND-2026-000004');

    $page = $this->actingAs($customer)->get(route('orders.index'));
    $page->assertStatus(200);
    $stats = $page->viewData('stats');
    expect($stats)->toBe([
        'total' => 4,
        'pending' => 1,
        'in_progress' => 1,
        'completed' => 1,
        'cancelled' => 1,
    ]);

    // Status filter: in_progress
    $this->actingAs($customer)
        ->get(route('orders.index', ['status' => 'in_progress']))
        ->assertStatus(200)
        ->assertSee('LND-2026-000002')
        ->assertDontSee('LND-2026-000001')
        ->assertDontSee('LND-2026-000003');

    // Date filter: 2026-11-10 to 2026-11-21 -> matches 000002 and 000003
    $this->actingAs($customer)
        ->get(route('orders.index', ['from_date' => '2026-11-10', 'to_date' => '2026-11-21']))
        ->assertStatus(200)
        ->assertSee('LND-2026-000002')
        ->assertSee('LND-2026-000003')
        ->assertDontSee('LND-2026-000001')
        ->assertDontSee('LND-2026-000004');

    // Search by invoice number
    $this->actingAs($customer)
        ->get(route('orders.index', ['q' => 'INV-2026-000999']))
        ->assertStatus(200)
        ->assertSee('LND-2026-000003')
        ->assertDontSee('LND-2026-000002');

    // Invalid date range rejected
    $this->actingAs($customer)
        ->get(route('orders.index', ['from_date' => '2026-11-25', 'to_date' => '2026-11-10']))
        ->assertSessionHasErrors(['to_date']);

    // No filter results empty state
    $this->actingAs($customer)
        ->get(route('orders.index', ['q' => 'NON-EXISTENT-999']))
        ->assertStatus(200)
        ->assertSee('No orders match your filters.')
        ->assertSee('Clear Filters');
});

test('shows No orders yet empty state and paginates 10 per page preserving filters', function () {
    $emptyCustomer = User::factory()->create(['email' => 'empty@example.com', 'email_verified_at' => now()]);
    $this->actingAs($emptyCustomer)
        ->get(route('orders.index'))
        ->assertStatus(200)
        ->assertSee('No orders yet.')
        ->assertSee('Book Laundry Service');

    $manyCustomer = User::factory()->create(['email' => 'many@example.com', 'email_verified_at' => now()]);
    for ($i = 1; $i <= 12; $i++) {
        makeCustomerOrder($manyCustomer, 'many@example.com', Booking::STATUS_PROCESSING, [], null, sprintf('LND-2026-%06d', $i));
    }

    $p1 = $this->actingAs($manyCustomer)->get(route('orders.index', ['status' => 'pending']));
    $p1->assertStatus(200);
    expect($p1->viewData('bookings')->count())->toBe(10);

    $p2 = $this->actingAs($manyCustomer)->get(route('orders.index', ['status' => 'pending', 'page' => 2]));
    $p2->assertStatus(200);
    expect($p2->viewData('bookings')->count())->toBe(2);
});
