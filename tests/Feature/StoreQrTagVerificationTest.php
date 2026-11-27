<?php

use App\Models\Booking;
use App\Models\LaundryTag;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\StoreUser;
use App\Services\LaundryTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use App\Models\BookingOperationalItem;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeVerifyAdmin(): Admin
{
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-laundry-tags', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Store Verify Admin',
        'email' => 'sv-admin-' . Str::random(5) . '@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $admin->assignRole($role);

    return $admin;
}

function makeVerifyStoreAndUser(string $name): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => Str::slug($name) . '-' . random_int(1000, 9999),
        'store_code' => 'STV-' . random_int(1000, 9999),
        'email' => Str::slug($name) . random_int(100, 999) . '@example.com',
        'phone' => '+44 2079460011',
        'address_line_1' => '100 High Street',
        'city' => 'London',
        'postcode' => 'W1D 2DZ',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $user = StoreUser::create([
        'store_id' => $store->id,
        'name' => $name . ' Staff',
        'email' => 'sv-staff-' . Str::random(6) . '@example.com',
        'password' => 'StorePass123!',
        'role' => 'owner',
        'is_active' => true,
    ]);

    return [$store, $user];
}

function makeVerifyDp(): DeliveryPerson
{
    $phone = '077008' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => 'Verify Driver',
        'date_of_birth' => '1991-03-10',
        'email' => 'dp-verify-' . Str::random(5) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '12 Baker Street',
        'city' => 'London',
        'residential_postcode' => 'NW1 6XE',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026!',
    ]);
}

function makeVerifyBooking(Store $store, DeliveryPerson $dp, Admin $admin, bool $finalized = true, bool $storeProcessing = true): Booking
{
    $stages = [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => now()->toIso8601String()],
    ];
    if ($finalized) {
        $stages[Booking::STATUS_FINALIZED] = ['completed_at' => now()->toIso8601String()];
    }
    if ($storeProcessing) {
        $stages[Booking::STATUS_DELIVERY_MAN_PICKED_UP] = ['completed_at' => now()->toIso8601String()];
        $stages[Booking::STATUS_STORE_RECEIVED] = ['completed_at' => now()->toIso8601String()];
    }

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Verify Customer',
        'customer_email' => 'verify-cust@example.com',
        'customer_phone' => '+44 7700900111',
        'address_snapshot' => ['selected_address' => '18 London Road, M1 2PF', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-28', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'currency' => 'GBP',
        'payment_gateway' => 'cod',
        'payment_status' => 'unpaid',
        'booking_status' => $storeProcessing ? Booking::STATUS_STORE_PROCESSING : Booking::STATUS_STORE_RECEIVED,
        'completed_operational_stages' => $stages,
        'assigned_store_id' => $store->id,
        'estimated_amount_minor' => 2500,
        'final_amount_minor' => 2500,
    ]);
    $booking->forceFill(['collection_otp_verified_at' => now()])->save();

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '100 High Street, London',
        'assigned_by_admin_id' => $admin->id,
        'is_current' => true,
        'postcode_matched' => true,
        'assigned_at' => now(),
    ]);

    $cat = ItemCategory::create(['name' => 'Wash ' . random_int(100, 999), 'slug' => 'wash-' . random_int(100, 999), 'is_active' => true]);
    $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Standard Wash', 'slug' => 'sw-' . random_int(100, 999), 'price' => 25.00, 'is_active' => true]);
    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $srv->id,
        'service_name' => $srv->name,
        'item_category_id' => $cat->id,
        'category_name' => $cat->name,
        'item_name' => 'Bag',
        'quantity' => 1,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => 2500,
        'actual_unit_price_minor' => 2500,
        'line_total_minor' => 2500,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-' . strtoupper(Str::random(6)),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => '18 London Road',
        'destination_location' => $store->name,
        'rate_minor' => 450,
        'currency' => 'GBP',
        'assigned_by_admin_id' => $admin->id,
        'assigned_at' => now(),
    ]);

    return $booking;
}

it('renders Store QR Tag Verification page and enforces store ownership & eligibility on lookup', function () {
    $admin = makeVerifyAdmin();
    [$storeA, $userA] = makeVerifyStoreAndUser('Store Alpha');
    [$storeB, $userB] = makeVerifyStoreAndUser('Store Beta');
    $dp = makeVerifyDp();

    $tagService = app(LaundryTagService::class);
    $tagService->generateBatch(3, $admin);
    $tagService->allocateToDeliveryPerson($dp, 3, $admin);

    $bookingA = makeVerifyBooking($storeA, $dp, $admin, true, true);
    $bookingNotReceived = makeVerifyBooking($storeA, $dp, $admin, true, false);

    $tag1 = LaundryTag::where('tag_code', 'FG-T-000001')->firstOrFail();
    $tag2 = LaundryTag::where('tag_code', 'FG-T-000002')->firstOrFail();

    LaundryTagBookingAssignment::create([
        'tag_id' => $tag1->id,
        'booking_id' => $bookingA->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $storeA->id,
        'package_label' => 'Bag 1',
        'bag_sequence' => 1,
        'active_lock' => 1,
        'status' => LaundryTagBookingAssignment::STATUS_ACTIVE,
        'assigned_at' => now(),
    ]);
    $tag1->update(['status' => LaundryTag::STATUS_ASSIGNED, 'current_booking_id' => $bookingA->id]);

    LaundryTagBookingAssignment::create([
        'tag_id' => $tag2->id,
        'booking_id' => $bookingNotReceived->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $storeA->id,
        'package_label' => 'Bag 1',
        'bag_sequence' => 1,
        'active_lock' => 1,
        'status' => LaundryTagBookingAssignment::STATUS_ACTIVE,
        'assigned_at' => now(),
    ]);
    $tag2->update(['status' => LaundryTag::STATUS_ASSIGNED, 'current_booking_id' => $bookingNotReceived->id]);

    $this->actingAs($userA, 'store')
        ->get(route('store.tag-verification.index'))
        ->assertOk()
        ->assertSee('QR Tag Verification');

    // Unknown tag rejected
    $this->actingAs($userA, 'store')
        ->postJson(route('store.tag-verification.verify'), ['tag_code' => 'FG-T-999999'])
        ->assertStatus(422);

    // Unassigned tag rejected
    $this->actingAs($userA, 'store')
        ->postJson(route('store.tag-verification.verify'), ['tag_code' => 'FG-T-000003'])
        ->assertStatus(422);

    // Another Store cannot access Store A's tag
    $this->actingAs($userB, 'store')
        ->postJson(route('store.tag-verification.verify'), ['tag_code' => 'FG-T-000001'])
        ->assertStatus(422)
        ->assertJsonMissing(['reference' => $bookingA->reference]);

    // Pre-receipt booking tag rejected
    $this->actingAs($userA, 'store')
        ->postJson(route('store.tag-verification.verify'), ['tag_code' => 'FG-T-000002'])
        ->assertStatus(422);

    // Valid manual verification works and does NOT complete processing
    $this->actingAs($userA, 'store')
        ->postJson(route('store.tag-verification.verify'), ['tag_code' => 'FG-T-000001', 'method' => 'manual'])
        ->assertOk()
        ->assertJsonPath('progress.total', 1)
        ->assertJsonPath('progress.completed', 0);

    expect(LaundryTagBookingAssignment::where('tag_id', $tag1->id)->first()->isVerifiedByStore())->toBeTrue()
        ->and(LaundryTagBookingAssignment::where('tag_id', $tag1->id)->first()->isStoreProcessingCompleted())->toBeFalse();
});

it('tracks multi-bag tag completion independently, blocks early Store Complete, and auto-completes Store Complete when all tags finish', function () {
    $admin = makeVerifyAdmin();
    [$store, $storeUser] = makeVerifyStoreAndUser('Mayfair Store');
    $dp = makeVerifyDp();

    $tagService = app(LaundryTagService::class);
    $tagService->generateBatch(2, $admin);
    $tagService->allocateToDeliveryPerson($dp, 2, $admin);

    $booking = makeVerifyBooking($store, $dp, $admin, true, true);
    $tag1 = LaundryTag::where('tag_code', 'FG-T-000001')->firstOrFail();
    $tag2 = LaundryTag::where('tag_code', 'FG-T-000002')->firstOrFail();

    $asg1 = LaundryTagBookingAssignment::create([
        'tag_id' => $tag1->id,
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'package_label' => 'Bag 1',
        'bag_sequence' => 1,
        'active_lock' => 1,
        'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
        'store_verified_at' => now(),
        'verified_by_store_user_id' => $storeUser->id,
    ]);
    $asg2 = LaundryTagBookingAssignment::create([
        'tag_id' => $tag2->id,
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'package_label' => 'Bag 2',
        'bag_sequence' => 2,
        'active_lock' => 1,
        'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
        'store_verified_at' => now(),
        'verified_by_store_user_id' => $storeUser->id,
    ]);
    $tag1->update(['status' => LaundryTag::STATUS_AT_STORE, 'current_booking_id' => $booking->id]);
    $tag2->update(['status' => LaundryTag::STATUS_AT_STORE, 'current_booking_id' => $booking->id]);

    // 1. Complete Bag 1 -> progress 1/2 (50%), booking remains store_processing
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.tag-verification.complete', $asg1))
        ->assertOk()
        ->assertJsonPath('progress.total', 2)
        ->assertJsonPath('progress.completed', 1)
        ->assertJsonPath('progress.remaining', 1)
        ->assertJsonPath('progress.percent', 50)
        ->assertJsonPath('auto_store_complete_status', 'in_progress');

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_STORE_PROCESSING);

    // 2. Manual Mark Store Complete MUST be blocked while Bag 2 is incomplete
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.mark-complete', $booking))
        ->assertStatus(422);

    // 3. Duplicate completion of Bag 1 is rejected
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.tag-verification.complete', $asg1))
        ->assertStatus(422);

    // 4. Complete Bag 2 -> progress 2/2 (100%) -> automatically triggers Mark Store Complete!
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.tag-verification.complete', $asg2))
        ->assertOk()
        ->assertJsonPath('progress.total', 2)
        ->assertJsonPath('progress.completed', 2)
        ->assertJsonPath('progress.remaining', 0)
        ->assertJsonPath('progress.percent', 100)
        ->assertJsonPath('auto_store_complete_status', 'completed');

    $booking->refresh();
    expect(in_array(Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true))->toBeTrue()
        ->and($booking->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP)
        ->and(LaundryTagEvent::where('event_type', LaundryTagEvent::EVENT_TAG_PROCESSING_COMPLETED)->count())->toBe(2)
        ->and(LaundryTagEvent::where('event_type', LaundryTagEvent::EVENT_MANIFEST_COMPLETED)->count())->toBe(1)
        ->and(LaundryTagEvent::where('event_type', LaundryTagEvent::EVENT_AUTO_STORE_COMPLETE)->count())->toBe(1);
});

it('keeps tag completion saved when all tags finish but Store Complete prerequisites are not yet satisfied', function () {
    $admin = makeVerifyAdmin();
    [$store, $storeUser] = makeVerifyStoreAndUser('Soho Store');
    $dp = makeVerifyDp();

    $tagService = app(LaundryTagService::class);
    $tagService->generateBatch(1, $admin);
    $tagService->allocateToDeliveryPerson($dp, 1, $admin);

    // Booking is NOT finalized yet
    $booking = makeVerifyBooking($store, $dp, $admin, false, true);
    $tag1 = LaundryTag::where('tag_code', 'FG-T-000001')->firstOrFail();

    $asg1 = LaundryTagBookingAssignment::create([
        'tag_id' => $tag1->id,
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'package_label' => 'Bag 1',
        'bag_sequence' => 1,
        'active_lock' => 1,
        'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
        'store_verified_at' => now(),
        'verified_by_store_user_id' => $storeUser->id,
    ]);
    $tag1->update(['status' => LaundryTag::STATUS_AT_STORE, 'current_booking_id' => $booking->id]);

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.tag-verification.complete', $asg1))
        ->assertStatus(422);

    $booking->refresh();
    expect($asg1->fresh()->isStoreProcessingCompleted())->toBeFalse()
        ->and(in_array(Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true))->toBeFalse();
});
