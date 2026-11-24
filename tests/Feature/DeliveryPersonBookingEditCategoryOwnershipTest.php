<?php

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalCategory;
use App\Models\BookingOperationalItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function makeCatEditDp(string $name): DeliveryPerson
{
    $phone = '077008' . random_int(10000, 99999);

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

function makeCatEditSetup(DeliveryPerson $dp): array
{
    $store = Store::create([
        'name' => 'Edit Store ' . random_int(100, 999),
        'slug' => 'edit-store-' . random_int(1000, 9999),
        'store_code' => 'EST-' . random_int(1000, 9999),
        'email' => 'estore' . random_int(1000, 9999) . '@example.com',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '5 Store St',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $custCat = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold-' . random_int(100, 999), 'is_active' => true]);
    $custSrv = Service::create(['item_category_id' => $custCat->id, 'name' => 'Mixed Wash', 'slug' => 'mixed-' . random_int(100, 999), 'price' => 15.00, 'is_active' => true]);
    $custSrv2 = Service::create(['item_category_id' => $custCat->id, 'name' => 'Delicate Wash', 'slug' => 'delicate-' . random_int(100, 999), 'price' => 20.00, 'is_active' => true]);

    $extraCat = ItemCategory::create(['name' => 'Dry Cleaning', 'slug' => 'dry-clean-' . random_int(100, 999), 'is_active' => true]);
    $extraSrv = Service::create(['item_category_id' => $extraCat->id, 'name' => 'Coat Clean', 'slug' => 'coat-' . random_int(100, 999), 'price' => 18.00, 'is_active' => true]);

    $adminCat = ItemCategory::create(['name' => 'Bedding & Curtains', 'slug' => 'bedding-' . random_int(100, 999), 'is_active' => true]);
    $adminSrv = Service::create(['item_category_id' => $adminCat->id, 'name' => 'Duvet Clean', 'slug' => 'duvet-' . random_int(100, 999), 'price' => 25.00, 'is_active' => true]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Cat Edit Customer',
        'customer_email' => 'catedit@example.com',
        'customer_phone' => '+447700900888',
        'address_snapshot' => ['address_line_1' => '100 Portland St', 'city' => 'Manchester', 'postcode' => 'M1 4GX'],
        'schedule_snapshot' => ['collection_date' => '2026-11-24', 'collection_slot' => '09:00 - 11:00'],
        'services_snapshot' => [['category_id' => $custCat->id, 'name' => 'Wash & Fold', 'option_name' => 'Mixed Wash']],
        'contact_snapshot' => [],
        'currency' => 'GBP',
        'estimated_amount_minor' => 2000,
        'service_fee_minor' => 200,
        'collection_delivery_fee_minor' => 300,
        'tip_amount_minor' => 0,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'final_amount_minor' => 2000,
        'payment_status' => 'pending',
        'payment_gateway' => 'cod',
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'completed_operational_stages' => [],
        'assigned_store_id' => $store->id,
        'version' => 1,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '5 Store St, Manchester',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    $custItem = BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $custSrv->id,
        'service_name' => $custSrv->name,
        'item_category_id' => $custCat->id,
        'category_name' => $custCat->name,
        'item_name' => 'Mixed Bag',
        'quantity' => 1,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => 1500,
        'actual_unit_price_minor' => 1500,
        'line_total_minor' => 1500,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    $asg = DeliveryAssignment::create([
        'assignment_reference' => 'ASG-CE-' . random_int(1000, 9999),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_location' => '100 Portland St',
        'destination_location' => $store->name,
        'assigned_at' => now(),
        'rate_minor' => 150,
        'currency' => 'GBP',
    ]);

    return compact('booking', 'store', 'custCat', 'custSrv', 'custSrv2', 'custItem', 'extraCat', 'extraSrv', 'adminCat', 'adminSrv', 'asg');
}

it('protects customer-added categories from Delivery Person removal while allowing item additions and edits inside them', function () {
    $dp = makeCatEditDp('Driver One');
    $s = makeCatEditSetup($dp);
    $booking = $s['booking'];

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('+ Add Service Category')
        ->assertSee('Customer Selected')
        ->assertDontSee('Remove Category');

    // Add a new item inside the customer-selected category & update existing item quantity
    $this->actingAs($dp, 'delivery_person')
        ->put(route('delivery-person.bookings.update', $booking), [
            'items' => [
                ['id' => $s['custItem']->id, 'quantity' => 2],
            ],
            'new_items' => [
                ['service_id' => $s['custSrv2']->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect(route('delivery-person.bookings.show', $booking));

    $booking->refresh();
    expect((int) $booking->final_amount_minor)->toBe(5500); // 2*1500 + 1*2000 + 200 + 300
    expect((int) $booking->version)->toBe(2);
    expect($booking->services_snapshot[0]['category_id'])->toBe($s['custCat']->id);

    // Crafted DELETE request for customer category is rejected (403)
    $this->actingAs($dp, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.categories.remove', [$booking, $s['custCat']]))
        ->assertForbidden();

    // Crafted PUT with removed_categories for customer category is rejected (403)
    $this->actingAs($dp, 'delivery_person')
        ->put(route('delivery-person.bookings.update', $booking), [
            'removed_categories' => [['category_id' => $s['custCat']->id, 'reason' => 'Force remove']],
        ])
        ->assertForbidden();
});

it('allows Collection Delivery Person to add a new service category, add items, and remove only their own added category', function () {
    $dp = makeCatEditDp('Driver Adder');
    $s = makeCatEditSetup($dp);
    $booking = $s['booking'];

    // Add extraCat as Delivery Person
    $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.categories.add', $booking), [
            'category_id' => $s['extraCat']->id,
        ])
        ->assertOk()
        ->assertJsonPath('category.origin_type', BookingOperationalCategory::ORIGIN_DELIVERY_PERSON)
        ->assertJsonPath('category.can_remove_category', true);

    $prov = BookingOperationalCategory::where('booking_id', $booking->id)
        ->where('item_category_id', $s['extraCat']->id)
        ->firstOrFail();
    expect($prov->origin_type)->toBe(BookingOperationalCategory::ORIGIN_DELIVERY_PERSON);
    expect((int) $prov->origin_actor_id)->toBe((int) $dp->id);

    // Page shows Added by Delivery Person (You) + Remove Category
    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Added by Delivery Person (You)')
        ->assertSee('Remove Category');

    // Add item in extraCat & save
    $this->actingAs($dp, 'delivery_person')
        ->put(route('delivery-person.bookings.update', $booking), [
            'items' => [['id' => $s['custItem']->id, 'quantity' => 1]],
            'new_items' => [['service_id' => $s['extraSrv']->id, 'quantity' => 1]],
        ])
        ->assertRedirect(route('delivery-person.bookings.show', $booking));

    $booking->refresh();
    expect((int) $booking->final_amount_minor)->toBe(3800); // 1500 + 1800 + 200 + 300

    // Remove Delivery Person's own category -> removes items, recalculates totals, keeps customer snapshot intact
    $this->actingAs($dp, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.categories.remove', [$booking, $s['extraCat']]), [
            'reason' => 'Not needed',
        ])
        ->assertOk();

    $booking->refresh();
    expect((int) $booking->final_amount_minor)->toBe(2000);
    expect($booking->operationalItems()->where('item_category_id', $s['extraCat']->id)->count())->toBe(0);
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'category_removed_by_dp')->exists())->toBeTrue();
});

it('protects admin-added categories, unknown-origin categories, and prevents reassigned Delivery Person B from removing Delivery Person A categories', function () {
    $dpA = makeCatEditDp('Driver A');
    $dpB = makeCatEditDp('Driver B');
    $s = makeCatEditSetup($dpA);
    $booking = $s['booking'];

    // 1. Admin-added category
    BookingOperationalCategory::create([
        'booking_id' => $booking->id,
        'item_category_id' => $s['adminCat']->id,
        'category_name' => $s['adminCat']->name,
        'origin_type' => BookingOperationalCategory::ORIGIN_ADMIN,
        'origin_actor_id' => 1,
    ]);

    $this->actingAs($dpA, 'delivery_person')
        ->get(route('delivery-person.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Added by Admin');

    $this->actingAs($dpA, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.categories.remove', [$booking, $s['adminCat']]))
        ->assertForbidden();

    // 2. Driver A adds extraCat
    $this->actingAs($dpA, 'delivery_person')
        ->postJson(route('delivery-person.bookings.categories.add', $booking), [
            'category_id' => $s['extraCat']->id,
        ])
        ->assertOk();

    // 3. Reassign collection to Driver B
    $s['asg']->update(['status' => DeliveryAssignment::STATUS_CANCELLED]);
    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-B-' . random_int(1000, 9999),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dpB->id,
        'store_id' => $s['store']->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'pickup_location' => '100 Portland St',
        'destination_location' => $s['store']->name,
        'assigned_at' => now(),
        'rate_minor' => 150,
        'currency' => 'GBP',
    ]);

    // Driver B sees A's category, but cannot remove A's category (403)
    $this->actingAs($dpB, 'delivery_person')
        ->get(route('delivery-person.bookings.edit', $booking))
        ->assertOk()
        ->assertSee($s['extraCat']->name)
        ->assertDontSee('Remove Category');

    $this->actingAs($dpB, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.categories.remove', [$booking, $s['extraCat']]))
        ->assertForbidden();

    // Provenance still belongs to Driver A
    $prov = BookingOperationalCategory::where('booking_id', $booking->id)
        ->where('item_category_id', $s['extraCat']->id)
        ->firstOrFail();
    expect((int) $prov->origin_actor_id)->toBe((int) $dpA->id);
});
