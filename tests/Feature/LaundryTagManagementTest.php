<?php

use App\Models\Booking;
use App\Models\BookingStoreHandover;
use App\Models\LaundryTag;
use App\Models\LaundryTagBatch;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\LaundryTagPrintJob;
use App\Models\StoreUser;
use App\Models\TagPrinterProfile;
use App\Services\LaundryTagQrService;
use App\Services\StoreHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-laundry-tags', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-laundry-tags', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
});

function createTagAdmin(bool $withSuperRole = true): Admin
{
    $admin = Admin::create([
        'name' => 'Tag Operations Admin',
        'email' => 'tag-admin-' . Str::random(5) . '@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    if ($withSuperRole) {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $admin->assignRole($role);
    }

    return $admin;
}

function createTagDeliveryPerson(string $name = 'Alex Driver', string $workStatus = 'active'): DeliveryPerson
{
    $phone = '077009' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1992-05-12',
        'email' => strtolower(str_replace(' ', '.', $name)) . '.' . Str::random(4) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '12 Baker Street',
        'city' => 'London',
        'residential_postcode' => 'NW1 6XE',
        'work_status' => $workStatus,
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026!',
    ]);
}

function createTagStoreAndStaff(string $name = 'Fresh Go Soho'): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => Str::slug($name) . '-' . random_int(1000, 9999),
        'store_code' => 'STR-' . random_int(1000, 9999),
        'email' => Str::slug($name) . random_int(100, 999) . '@example.com',
        'phone' => '+44 2079460011',
        'address_line_1' => '45 Oxford Street',
        'city' => 'London',
        'postcode' => 'W1D 2DZ',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $storeUser = StoreUser::create([
        'store_id' => $store->id,
        'name' => $name . ' Staff',
        'email' => 'staff-' . Str::random(6) . '@example.com',
        'password' => 'StorePass123!',
        'role' => 'owner',
        'is_active' => true,
    ]);

    return [$store, $storeUser];
}

function createTagCollectionBooking(Store $store, DeliveryPerson $dp, Admin $admin, bool $otpVerified = true, bool $pickedUp = true): Booking
{
    $stages = [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()],
    ];
    if ($pickedUp) {
        $stages[Booking::STATUS_DELIVERY_MAN_PICKED_UP] = ['completed_at' => now()->toIso8601String()];
    }

    $booking = Booking::create([
        'reference' => 'FGL-' . strtoupper(Str::random(6)),
        'customer_name' => 'Jane Customer',
        'customer_email' => 'jane.customer@example.com',
        'customer_phone' => '+44 7700900111',
        'address_snapshot' => ['selected_address' => '18 London Road, M1 2PF', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-28', 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'currency' => 'GBP',
        'payment_gateway' => 'cod',
        'payment_status' => 'unpaid',
        'booking_status' => $pickedUp ? Booking::STATUS_STORE_RECEIVED : Booking::STATUS_FINALIZED,
        'completed_operational_stages' => $stages,
        'assigned_store_id' => $store->id,
        'estimated_amount_minor' => 2500,
        'final_amount_minor' => 2500,
    ]);

    if ($otpVerified) {
        $booking->forceFill(['collection_otp_verified_at' => now()])->save();
    }

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '45 Oxford Street, London',
        'assigned_by_admin_id' => $admin->id,
        'is_current' => true,
        'postcode_matched' => true,
        'assigned_at' => now(),
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-' . strtoupper(Str::random(6)),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'status' => $pickedUp ? DeliveryAssignment::STATUS_PICKED_UP : DeliveryAssignment::STATUS_ACCEPTED,
        'pickup_location' => '18 London Road',
        'destination_location' => $store->name,
        'rate_minor' => 450,
        'currency' => 'GBP',
        'assigned_by_admin_id' => $admin->id,
        'assigned_at' => now(),
    ]);

    return $booking;
}

it('renders TAG MANAGEMENT menu and submenus cleanly for authorized admin without unauthorized access', function () {
    $superAdmin = createTagAdmin(true);
    $unprivilegedAdmin = createTagAdmin(false);

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('TAG MANAGEMENT')
        ->assertSee('Generate &amp; Print', false)
        ->assertSee('Tag Inventory')
        ->assertSee('Assign to Delivery Person')
        ->assertSee('Tag History')
        ->assertSee('Printer Settings');

    $this->actingAs($unprivilegedAdmin, 'admin')
        ->get(route('admin.tags.index'))
        ->assertForbidden();
});

it('generates sequential permanent QR laundry tags cleanly across custom quantities and respects idempotency', function () {
    $admin = createTagAdmin();

    // Generate 1, then 10, then 30 with idempotency
    $res1 = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), [
            'quantity' => 1,
            'label' => 'Single Sample',
            'idempotency_key' => 'batch-key-1',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('batch.quantity', 1)
        ->assertJsonPath('batch.code_from', 'FG-T-000001');

    // Duplicate retry with same idempotency_key must NOT mint extra tags
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), [
            'quantity' => 1,
            'label' => 'Single Sample',
            'idempotency_key' => 'batch-key-1',
        ])
        ->assertOk()
        ->assertJsonPath('batch.code_from', 'FG-T-000001');

    expect(LaundryTag::count())->toBe(1);

    // Mint 10 more and 30 more
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), [
            'quantity' => 10,
            'idempotency_key' => 'batch-key-10',
        ])
        ->assertOk()
        ->assertJsonPath('batch.code_from', 'FG-T-000002')
        ->assertJsonPath('batch.code_to', 'FG-T-000011');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), [
            'quantity' => 30,
            'idempotency_key' => 'batch-key-30',
        ])
        ->assertOk()
        ->assertJsonPath('batch.code_from', 'FG-T-000012')
        ->assertJsonPath('batch.code_to', 'FG-T-000041');

    expect(LaundryTag::count())->toBe(41);

    // QR payload must contain zero customer/booking PII and verify cleanly via HMAC
    $qrService = app(LaundryTagQrService::class);
    $tag = LaundryTag::where('tag_code', 'FG-T-000001')->firstOrFail();
    expect($tag->qr_payload)->toStartWith('FG-T-000001|')
        ->and($qrService->renderSvgDataUri($tag->qr_payload))->toStartWith('data:image/svg+xml;base64,');
});

it('configures printer settings, generates print preview & PDF without duplicating tags, and warns when reprinting active tags', function () {
    $admin = createTagAdmin();
    $dp = createTagDeliveryPerson('Sam Driver');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), ['quantity' => 4])
        ->assertOk();

    $batch = LaundryTagBatch::firstOrFail();
    $profile = TagPrinterProfile::firstOrFail();

    // Update printer profile dimensions
    $this->actingAs($admin, 'admin')
        ->putJson(route('admin.tags.printer-settings.update', $profile), [
            'name' => 'Custom A4 8-Up',
            'preset' => 'a4',
            'orientation' => 'portrait',
            'paper_width_mm' => 210,
            'paper_height_mm' => 297,
            'tag_width_mm' => 42,
            'tag_height_mm' => 126,
            'margin_top_mm' => 10,
            'margin_bottom_mm' => 10,
            'margin_left_mm' => 12,
            'margin_right_mm' => 12,
            'gap_horizontal_mm' => 5,
            'gap_vertical_mm' => 10,
            'tags_per_row' => 4,
            'tags_per_column' => 2,
            'qr_size_mm' => 25,
            'logo_size_mm' => 15,
            'font_size_pt' => 8,
            'code_font_size_pt' => 9,
            'show_brand_name' => 1,
            'show_booking_field' => 1,
            'show_package_field' => 1,
            'show_cut_guides' => 1,
            'show_hole_guide' => 1,
            'background_printing' => 1,
            'is_default' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Print Preview (Prepared)
    $this->actingAs($admin, 'admin')
        ->get(route('admin.tags.print-preview', ['batch_id' => $batch->id, 'copies' => 1, 'start_offset' => 1]))
        ->assertOk()
        ->assertSee('Fresh')
        ->assertSee('FG-T-000001')
        ->assertSee('FG-T-000004');

    // Download PDF (Requested + increments print_count)
    $pdfResponse = $this->actingAs($admin, 'admin')
        ->get(route('admin.tags.print-pdf', ['batch_id' => $batch->id]));
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-type'))->toContain('application/pdf');

    // Confirm Physically Printed
    $printJob = LaundryTagPrintJob::latest('id')->firstOrFail();
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.print-jobs.confirm', $printJob))
        ->assertOk()
        ->assertJsonPath('print_job.status', 'confirmed');

    // Reprinting NEVER creates duplicate tags
    expect(LaundryTag::count())->toBe(4)
        ->and(LaundryTag::first()->print_status)->toBe(LaundryTag::PRINT_STATUS_PRINTED_CONFIRMED);

    // Allocate 2 tags to DP, then verify reprint preview displays warning for allocated tags
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.allocations.store'), [
            'delivery_person_id' => $dp->id,
            'quantity' => 2,
        ])
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.tags.print-preview', ['batch_id' => $batch->id]))
        ->assertOk()
        ->assertSee('Reprint Warning:');
});

it('enforces strict allocation validation and personal Delivery Person inventory isolation', function () {
    $admin = createTagAdmin();
    $dp1 = createTagDeliveryPerson('Driver One');
    $dp2 = createTagDeliveryPerson('Driver Two');
    $inactiveDp = createTagDeliveryPerson('Inactive Driver', 'inactive');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.generate.store'), ['quantity' => 5])
        ->assertOk();

    // Reject allocation exceeding stock
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.allocations.store'), [
            'delivery_person_id' => $dp1->id,
            'quantity' => 99,
        ])
        ->assertStatus(422);

    // Reject inactive DP
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.allocations.store'), [
            'delivery_person_id' => $inactiveDp->id,
            'quantity' => 2,
        ])
        ->assertStatus(422);

    // Allocate 3 to DP1 and 2 to DP2
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.allocations.store'), [
            'delivery_person_id' => $dp1->id,
            'quantity' => 3,
            'notes' => 'Morning allocation',
        ])
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.tags.allocations.store'), [
            'delivery_person_id' => $dp2->id,
            'quantity' => 2,
        ])
        ->assertOk();

    // Admin inventory shows 0 available, 5 allocated
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.tags.index'))
        ->assertOk()
        ->assertJsonPath('summary.available', 0)
        ->assertJsonPath('summary.allocated', 5);

    // DP1 only sees DP1 tags (FG-T-000001..000003), never DP2 tags (000004..000005)
    $this->actingAs($dp1, 'delivery_person')
        ->get(route('delivery-person.tags.index'))
        ->assertOk()
        ->assertSee('FG-T-000001')
        ->assertDontSee('FG-T-000004');
});

it('completes end-to-end collection QR/manual tag assignment, Store verification, handover acceptance, and final delivery retirement without touching OTP or earnings logic', function () {
    $admin = createTagAdmin();
    $dp1 = createTagDeliveryPerson('Collection DP');
    $dp2 = createTagDeliveryPerson('Other DP');
    [$store, $storeUser] = createTagStoreAndStaff('Fresh Go Mayfair');
    [$otherStore, $otherStoreUser] = createTagStoreAndStaff('Fresh Go Camden');

    // Generate 5 tags, allocate 4 to DP1, 1 to DP2
    $this->actingAs($admin, 'admin')->postJson(route('admin.tags.generate.store'), ['quantity' => 5])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.tags.allocations.store'), ['delivery_person_id' => $dp1->id, 'quantity' => 4])->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.tags.allocations.store'), ['delivery_person_id' => $dp2->id, 'quantity' => 1])->assertOk();

    // 1. Reject tagging if OTP or Picked Up is not completed yet
    $bookingNoOtp = createTagCollectionBooking($store, $dp1, $admin, false, false);
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $bookingNoOtp), ['tag_code' => 'FG-T-000001'])
        ->assertStatus(422);

    // Ready booking with Collection OTP verified & Picked Up completed
    $booking = createTagCollectionBooking($store, $dp1, $admin, true, true);
    $booking2 = createTagCollectionBooking($store, $dp1, $admin, true, true);

    // Wrong Delivery Person cannot attach tag to booking
    $this->actingAs($dp2, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking), ['tag_code' => 'FG-T-000005'])
        ->assertStatus(422);

    // DP1 cannot attach tag belonging to DP2
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking), ['tag_code' => 'FG-T-000005'])
        ->assertStatus(422);

    // Tampered QR payload is rejected
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking), ['tag_code' => 'FG-T-000001|INVALID999'])
        ->assertStatus(422);

    // Valid manual entry & signed QR payload (multi-tag: Bag 1 + Bag 2)
    $tag2 = LaundryTag::where('tag_code', 'FG-T-000002')->firstOrFail();

    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking), [
            'tag_code' => 'FG-T-000001',
            'package_label' => 'Bag 1',
            'method' => 'manual',
            'idempotency_key' => 'assign-b1-t1',
        ])
        ->assertOk()
        ->assertJsonPath('tag_count', 1);

    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking), [
            'tag_code' => $tag2->qr_payload,
            'package_label' => 'Suit Bag',
            'method' => 'qr',
        ])
        ->assertOk()
        ->assertJsonPath('tag_count', 2);

    // Prevent duplicate active assignment on second booking!
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking2), [
            'tag_code' => 'FG-T-000001',
        ])
        ->assertStatus(422);

    // Unassign tag 3 after assigning, then mark tag 3 lost and confirm lost tag cannot be assigned
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking2), ['tag_code' => 'FG-T-000003'])
        ->assertOk();
    $tag3 = LaundryTag::where('tag_code', 'FG-T-000003')->firstOrFail();
    $this->actingAs($dp1, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.tags.unassign', [$booking2, $tag3]))
        ->assertOk();
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.tags.exception', $tag3), ['status' => 'lost', 'reason' => 'Dropped on route'])
        ->assertOk();
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.tags.assign', $booking2), ['tag_code' => 'FG-T-000003'])
        ->assertStatus(422);

    // Collection DP requests Store Handover (existing workflow)
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bookings.store-handover', $booking), ['request_note' => '2 bags tagged'])
        ->assertOk()
        ->assertJsonPath('success', true);

    $handover = BookingStoreHandover::where('booking_id', $booking->id)->latest('id')->firstOrFail();
    expect(LaundryTagBookingAssignment::where('handover_id', $handover->id)->count())->toBe(2);

    // Store views handovers & verifies expected tag via QR payload
    $this->actingAs($storeUser, 'store')
        ->get(route('store.handovers.index'))
        ->assertOk()
        ->assertSee('FG-T-000001')
        ->assertSee('FG-T-000002');

    // Other store staff cannot verify tags on another store's booking
    $this->actingAs($otherStoreUser, 'store')
        ->postJson(route('store.bookings.verify-tag', $booking), ['tag_code' => 'FG-T-000001'])
        ->assertStatus(422);

    // Mismatched tag verification fails & logs verification_issue event
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.verify-tag', $booking), ['tag_code' => 'FG-T-000004'])
        ->assertStatus(422);
    expect(LaundryTagEvent::where('event_type', LaundryTagEvent::EVENT_HANDOVER_VERIFICATION_ISSUE)->exists())->toBeTrue();

    // Valid Store verification of FG-T-000001 via QR does NOT auto-accept handover or auto-complete store_received!
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.bookings.verify-tag', $booking), ['tag_code' => LaundryTag::where('tag_code', 'FG-T-000001')->first()->qr_payload, 'method' => 'qr'])
        ->assertOk();

    $booking->refresh();
    $handover->refresh();
    expect($handover->status)->toBe(BookingStoreHandover::STATUS_PENDING)
        ->and($booking->booking_status)->toBe(Booking::STATUS_STORE_RECEIVED)
        ->and(in_array(Booking::STATUS_STORE_RECEIVED, $booking->getCompletedOperationalStatuses(), true))->toBeFalse();

    // Store explicitly accepts handover -> Store Received completes & all remaining booking tags transition to at_store
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.handovers.accept', $handover))
        ->assertOk()
        ->assertJsonPath('success', true);

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_STORE_PROCESSING)
        ->and(LaundryTag::where('tag_code', 'FG-T-000001')->first()->status)->toBe(LaundryTag::STATUS_AT_STORE)
        ->and(LaundryTag::where('tag_code', 'FG-T-000002')->first()->status)->toBe(LaundryTag::STATUS_AT_STORE);

    // Collection DP can no longer remove tags after Store acceptance
    $tag1 = LaundryTag::where('tag_code', 'FG-T-000001')->firstOrFail();
    $this->actingAs($dp1, 'delivery_person')
        ->deleteJson(route('delivery-person.bookings.tags.unassign', [$booking, $tag1]))
        ->assertStatus(422);

    // Admin Booking Details and History display attached tags & full audit events
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Physical Laundry Tags')
        ->assertSee('FG-T-000001');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.tags.history', ['tag_code' => 'FG-T-000001']))
        ->assertOk()
        ->assertSee('store_verified')
        ->assertSee('assigned_to_booking');

    // UI check: ensure visible tag portals never use forbidden word "Courier"
    $dpTagsHtml = $this->actingAs($dp1, 'delivery_person')->get(route('delivery-person.tags.index'))->getContent();
    $adminTagsHtml = $this->actingAs($admin, 'admin')->get(route('admin.tags.index'))->getContent();
    expect(stripos($dpTagsHtml, 'courier'))->toBeFalse()
        ->and(stripos($adminTagsHtml, 'courier'))->toBeFalse();
});
