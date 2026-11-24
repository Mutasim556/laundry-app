<?php

use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\StoreUser;
use App\Services\StoreOperationalStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function makeStoreStatusSetup(): array
{
    $store = Store::create([
        'name' => 'Status Test Store',
        'slug' => 'status-test-store-' . random_int(1000, 9999),
        'store_code' => 'STS-' . random_int(1000, 9999),
        'email' => 'sts' . random_int(1000, 9999) . '@example.com',
        'phone' => '+44 1614960202',
        'address_line_1' => '23 Crowder Street',
        'city' => 'London',
        'postcode' => 'E1 0BJ',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $storeUser = StoreUser::create([
        'store_id' => $store->id,
        'name' => 'Status Manager',
        'email' => 'stsmgr' . random_int(1000, 9999) . '@example.com',
        'password' => 'Secret123!',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Status Customer',
        'customer_email' => 'sts-cust@example.com',
        'customer_phone' => '+447700900777',
        'address_snapshot' => ['selected_address' => '18 London Road', 'postcode' => 'M1 2PF'],
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
        'booking_status' => Booking::STATUS_PROCESSING,
        'completed_operational_stages' => [],
        'confirmed_at' => now(),
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

    $cat = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wf-' . random_int(100, 999), 'is_active' => true]);
    $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Wash Service', 'slug' => 'ws-' . random_int(100, 999), 'price' => 20.00, 'is_active' => true]);
    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $srv->id,
        'service_name' => $srv->name,
        'item_category_id' => $cat->id,
        'category_name' => $cat->name,
        'item_name' => 'Bag',
        'quantity' => 1,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => 2000,
        'actual_unit_price_minor' => 2000,
        'line_total_minor' => 2000,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    return [$store, $storeUser, $booking];
}

it('shows exact last completed stage on Store list and details without marking next pending stage completed', function () {
    [$store, $storeUser, $booking] = makeStoreStatusSetup();
    $svc = app(StoreOperationalStatusService::class);

    // 0. Empty completion history -> Pending
    $s0 = $svc->getStatusForBooking($booking);
    expect($s0['operational_status_label'])->toBe('Pending');
    expect($s0['store_workflow_finished'])->toBeFalse();

    // 1. Delivery Person Picked Up Done -> next pending is Store Received, last completed is Picked Up
    $stages = [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_PICKED_UP => ['completed_at' => now()->toIso8601String()],
    ];
    $booking->update([
        'booking_status' => Booking::STATUS_STORE_RECEIVED,
        'completed_operational_stages' => $stages,
    ]);

    $this->actingAs($storeUser, 'store')->get(route('store.bookings.index'))
        ->assertOk()
        ->assertSee('Picked Up');
    $this->actingAs($storeUser, 'store')->get(route('store.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Operational Status: Picked Up')
        ->assertSee('Store Received (Current)')
        ->assertDontSee('Store Received — Completed')
        ->assertDontSee('Store Workflow Finished');

    // 2. Store Received Done -> next pending is Store Processing, last completed is Store Received
    $stages[Booking::STATUS_STORE_RECEIVED] = ['completed_at' => now()->toIso8601String()];
    $booking->update([
        'booking_status' => Booking::STATUS_STORE_PROCESSING,
        'completed_operational_stages' => $stages,
    ]);

    $this->actingAs($storeUser, 'store')->get(route('store.bookings.index'))
        ->assertOk()
        ->assertSee('Store Received');
    $this->actingAs($storeUser, 'store')->get(route('store.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Operational Status: Store Received')
        ->assertSee('Store Processing (Current)')
        ->assertDontSee('Store Processing — Completed');

    // 3. Store Processing Done -> next pending is Store Complete, last completed is Store Processing
    $stages[Booking::STATUS_STORE_PROCESSING] = ['completed_at' => now()->toIso8601String()];
    $booking->update([
        'booking_status' => Booking::STATUS_STORE_COMPLETE,
        'completed_operational_stages' => $stages,
    ]);

    $this->actingAs($storeUser, 'store')->get(route('store.bookings.index'))
        ->assertOk()
        ->assertSee('Store Processing');
    $this->actingAs($storeUser, 'store')->get(route('store.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Operational Status: Store Processing')
        ->assertSee('Store Complete (Current)')
        ->assertDontSee('Store Complete — Completed');

    // 4. Store Complete Done -> Store Workflow Finished, stays Store Complete even at On Delivery & Completed
    $stages[Booking::STATUS_STORE_COMPLETE] = ['completed_at' => now()->toIso8601String()];
    $booking->update([
        'booking_status' => Booking::STATUS_ON_DELIVERY,
        'completed_operational_stages' => $stages,
    ]);

    $this->actingAs($storeUser, 'store')->get(route('store.bookings.index'))
        ->assertOk()
        ->assertSee('Store Complete')
        ->assertSee('Store Workflow Finished');
    $this->actingAs($storeUser, 'store')->get(route('store.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Operational Status: Store Complete')
        ->assertSee('Store Workflow Finished');

    $stages[Booking::STATUS_ON_DELIVERY] = ['completed_at' => now()->toIso8601String()];
    $stages[Booking::STATUS_COMPLETED] = ['completed_at' => now()->toIso8601String()];
    $booking->update([
        'booking_status' => Booking::STATUS_COMPLETED,
        'completed_operational_stages' => $stages,
    ]);

    $sFinal = $svc->getStatusForBooking($booking->fresh());
    expect($sFinal['operational_status_label'])->toBe('Store Complete');
    expect($sFinal['store_workflow_finished'])->toBeTrue();
});
