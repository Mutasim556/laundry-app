<?php

use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Models\User;
use Database\Seeders\BookingFeeSettingSeeder;
use Illuminate\Support\Facades\Mail;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['view-bookings', 'manage-bookings', 'cancel-bookings', 'override-booking-prices'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }

    (new BookingFeeSettingSeeder())->run();
});

function makeBookingAdmin(array $permissions = ['view-bookings', 'manage-bookings', 'cancel-bookings', 'override-booking-prices']): Admin
{
    $admin = Admin::create([
        'name' => 'Booking Admin ' . uniqid(),
        'email' => 'bookingadmin+' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

function seedCatalogForBookingTests(): array
{
    $kgUnit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $itemUnit = Unit::firstOrCreate(['name' => 'Piece'], ['short_name' => 'pc', 'is_active' => true]);

    $wfCat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-' . uniqid(), 'is_active' => true]);
    $dcCat = ItemCategory::firstOrCreate(['name' => 'Dry Cleaning'], ['slug' => 'dry-cleaning-' . uniqid(), 'is_active' => true]);

    $wfService = Service::create([
        'unit_id' => $kgUnit->id,
        'item_category_id' => $wfCat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold-' . uniqid(),
        'price' => 500.00,
        'is_active' => true,
    ]);

    $dcService = Service::create([
        'unit_id' => $itemUnit->id,
        'item_category_id' => $dcCat->id,
        'name' => 'Suit Dry Clean',
        'slug' => 'suit-dry-clean-' . uniqid(),
        'price' => 300.00,
        'is_active' => true,
    ]);

    $suitItem = Item::create([
        'item_category_id' => $dcCat->id,
        'name' => 'Two-Piece Suit',
        'slug' => 'two-piece-suit-' . uniqid(),
        'is_active' => true,
    ]);

    return compact('kgUnit', 'itemUnit', 'wfCat', 'dcCat', 'wfService', 'dcService', 'suitItem');
}

function makeConfirmedBooking(array $catalog, array $overrides = []): Booking
{
    $user = User::factory()->create();

    return Booking::create(array_merge([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'user_id' => $user->id,
        'customer_name' => 'Alice Customer',
        'customer_email' => 'alice+' . uniqid() . '@example.com',
        'customer_phone' => '+8801711111111',
        'customer_type' => 'individual',
        'address_snapshot' => [
            'full_address' => '10 Downing Street, London',
            'postcode' => 'SW1A 2AA',
            'address_details' => 'Flat 1',
        ],
        'schedule_snapshot' => [
            'collection_date' => '2026-11-25',
            'collection_slot' => '09:00 - 11:00',
            'delivery_date' => '2026-11-27',
            'delivery_slot' => '14:00 - 16:00',
        ],
        'services_snapshot' => [
            [
                'service_id' => $catalog['wfService']->id,
                'name' => $catalog['wfService']->name,
                'category_name' => $catalog['wfCat']->name,
                'unit_name' => 'Kilogram',
                'price' => 500.00,
                'is_wash_fold' => true,
                'pricing_mode' => 'weight',
            ],
        ],
        'contact_snapshot' => [
            'name' => 'Alice Customer',
            'email' => 'alice@example.com',
        ],
        'fee_snapshot' => [
            'service_fee' => ['type' => 'fixed', 'value' => 50, 'amount_minor' => 5000],
            'collection_delivery_fee' => ['type' => 'fixed', 'value' => 100, 'amount_minor' => 10000],
        ],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => Booking::STATUS_CONFIRMED,
        'is_locked' => true,
        'version' => 1,
        'currency' => 'BDT',
        'estimated_amount_minor' => 65000,
        'service_fee_minor' => 5000,
        'service_fee_override_mode' => 'default',
        'collection_delivery_fee_minor' => 10000,
        'delivery_fee_override_mode' => 'default',
        'discount_minor' => 0,
        'tax_minor' => 0,
        'final_amount_minor' => null,
        'confirmed_at' => now(),
    ], $overrides));
}

it('enforces permissions on admin booking management routes', function () {
    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog);
    $noPermAdmin = makeBookingAdmin([]);

    $this->actingAs($noPermAdmin, 'admin')
        ->get(route('admin.bookings.index'))
        ->assertForbidden();

    $this->actingAs($noPermAdmin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertForbidden();

    $this->actingAs($noPermAdmin, 'admin')
        ->get(route('admin.bookings.edit', $booking))
        ->assertForbidden();

    $this->actingAs($noPermAdmin, 'admin')
        ->post(route('admin.bookings.cancel', $booking), ['cancellation_reason' => 'Test'])
        ->assertForbidden();
});

it('filters and searches bookings and shows booking details', function () {
    $catalog = seedCatalogForBookingTests();
    $b1 = makeConfirmedBooking($catalog, ['reference' => 'LND-2026-111001', 'customer_name' => 'Zara Khan', 'payment_gateway' => 'stripe']);
    $b2 = makeConfirmedBooking($catalog, ['reference' => 'LND-2026-111002', 'customer_name' => 'Bob Smith', 'payment_gateway' => 'cod']);

    $admin = makeBookingAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.index', ['q' => 'Zara', 'payment_gateway' => 'stripe']))
        ->assertOk()
        ->assertSee('LND-2026-111001')
        ->assertDontSee('LND-2026-111002');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $b1))
        ->assertOk()
        ->assertSee('LND-2026-111001')
        ->assertSee('10 Downing Street, London')
        ->assertSee('Services &amp; Items', false)
        ->assertSee('Items Subtotal')
        ->assertDontSee('Original Confirmed Snapshot');
});

it('allows admin to add service, add item, and remove operational item with reason while preserving original snapshot', function () {
    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog);
    $admin = makeBookingAdmin();

    // Opening edit initializes operational items from snapshot
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Mixed Wash & Fold');

    expect($booking->operationalItems()->count())->toBe(1);

    // Add item + qty
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.items.add', $booking), [
            'item_id' => $catalog['suitItem']->id,
            'service_id' => $catalog['dcService']->id,
            'quantity' => 2,
            'adjustment_reason' => 'Added 2 suits at sorting',
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    $dcLine = $booking->operationalItems()->where('item_id', $catalog['suitItem']->id)->first();
    expect($dcLine)->not->toBeNull()
        ->and($dcLine->quantity)->toBe(2)
        ->and($dcLine->line_total_minor)->toBe(60000); // 2 * 300.00 BDT = 60000 minor

    // Remove requires reason
    $this->actingAs($admin, 'admin')
        ->delete(route('admin.bookings.items.remove', [$booking, $dcLine]), [])
        ->assertSessionHasErrors('adjustment_reason');

    // Remove with reason succeeds and preserves original customer snapshot
    $origSnapshot = $booking->services_snapshot;
    $this->actingAs($admin, 'admin')
        ->delete(route('admin.bookings.items.remove', [$booking, $dcLine]), [
            'adjustment_reason' => 'Customer cancelled suit dry cleaning at pickup',
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    expect($booking->operationalItems()->where('id', $dcLine->id)->exists())->toBeFalse()
        ->and($booking->fresh()->services_snapshot)->toEqual($origSnapshot)
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'item_removed')->exists())->toBeTrue();
});

it('computes Wash & Fold decimal weight and price overrides server-side without mutating master catalog', function () {
    BookingFeeSetting::where('fee_code', 'service_fee')->update(['fee_value' => 50.00, 'fee_value_minor' => 5000]);
    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog);
    $admin = makeBookingAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.bookings.edit', $booking))->assertOk();
    $wfLine = $booking->operationalItems()->firstOrFail();

    // Update weight to 4.25 kg and override price from 500 to 400 with reason
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->fresh()->version,
            'items' => [
                $wfLine->id => [
                    'weight_kg' => 4.25,
                    'actual_unit_price' => 400.00,
                    'adjustment_reason' => 'Loyalty discount per kg',
                ],
            ],
            'service_fee_override_mode' => 'waived',
            'service_fee_override_reason' => 'First order fee waiver',
            'delivery_fee_override_mode' => 'custom',
            'delivery_fee_custom' => 50.00,
            'delivery_fee_override_reason' => 'Reduced local delivery',
            'discount' => 0,
            'tax' => 0,
            'operational_notes' => 'Weighed on scale #2',
            'booking_status' => Booking::STATUS_PROCESSING,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    $booking->refresh();
    $wfLine->refresh();

    // 4.25 * 400.00 = 1700.00 BDT = 170000 minor + 0 service fee + 5000 delivery = 175000 minor
    expect((float) $wfLine->weight_kg)->toBe(4.25)
        ->and($wfLine->actual_unit_price_minor)->toBe(40000)
        ->and($wfLine->line_total_minor)->toBe(170000)
        ->and($wfLine->is_price_overridden)->toBeTrue()
        ->and($booking->service_fee_minor)->toBe(0)
        ->and($booking->collection_delivery_fee_minor)->toBe(5000)
        ->and($booking->final_amount_minor)->toBe(175000)
        ->and($booking->booking_status)->toBe(Booking::STATUS_PROCESSING)
        ->and((float) $catalog['wfService']->fresh()->price)->toBe(500.00)
        ->and((float) BookingFeeSetting::where('fee_code', 'service_fee')->first()->fee_value)->toBe(50.0);
});

it('requires override-booking-prices permission and reason for unit price override and rejects stale version', function () {
    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog);

    $adminNoPriceOverride = makeBookingAdmin(['view-bookings', 'manage-bookings']);
    $this->actingAs($adminNoPriceOverride, 'admin')->get(route('admin.bookings.edit', $booking))->assertOk();
    $wfLine = $booking->operationalItems()->firstOrFail();

    // Attempt unit price override without permission
    $this->actingAs($adminNoPriceOverride, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->fresh()->version,
            'items' => [
                $wfLine->id => [
                    'weight_kg' => 3.0,
                    'actual_unit_price' => 350.00,
                    'adjustment_reason' => 'Trying to override',
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
        ])
        ->assertSessionHasErrors("items.{$wfLine->id}.actual_unit_price");

    // Stale version rejected (optimistic locking)
    $this->actingAs($adminNoPriceOverride, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => 999,
            'items' => [
                $wfLine->id => [
                    'weight_kg' => 3.0,
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
        ])
        ->assertSessionHasErrors('version');
});

it('cancels booking with reason, preserves record and payment info, logs audit, and emails customer', function () {
    Mail::fake();

    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog, [
        'payment_gateway' => 'stripe',
        'payment_status' => 'authorized',
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);
    $admin = makeBookingAdmin();

    // Reason is required
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.cancel', $booking), [])
        ->assertSessionHasErrors('cancellation_reason');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.cancel', $booking), [
            'cancellation_reason' => 'Customer requested cancellation before collection',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking));

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_CANCELLED)
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancelled_by_admin_id)->toBe($admin->id)
        ->and($booking->cancellation_reason)->toBe('Customer requested cancellation before collection')
        ->and($booking->stripe_payment_intent_id)->toBe('pi_test_123')
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'cancelled')->exists())->toBeTrue();

    Mail::assertSent(BookingNotificationMail::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->customer_email);
    });
});

it('excludes unpriced parent category placeholders, loads category services via JSON, adds services via AJAX, and enforces Wash & Fold mutual exclusivity', function () {
    $catalog = seedCatalogForBookingTests();
    $separateWf = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['wfCat']->id,
        'name' => 'Separate Wash & Fold',
        'slug' => 'separate-wash-fold-' . uniqid(),
        'price' => 450.00,
        'is_active' => true,
    ]);
    $emptyCat = ItemCategory::create([
        'name' => 'Repairs',
        'slug' => 'repairs-' . uniqid(),
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog, [
        'services_snapshot' => [
            [
                'category_id' => $catalog['wfCat']->id,
                'option_service_id' => $catalog['wfService']->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold',
                'is_wash_fold' => true,
                'price' => 500.00,
                'unit' => 'kg',
            ],
            [
                'category_id' => $catalog['dcCat']->id,
                'option_service_id' => null,
                'name' => 'Dry Cleaning',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
        ],
    ]);

    $admin = makeBookingAdmin();

    // Opening edit skips the unpriced Dry Cleaning category placeholder
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Confirmed Snapshot:')
        ->assertSee('Customer Selected');

    expect($booking->operationalItems()->count())->toBe(1)
        ->and($booking->operationalItems()->first()->service_name)->toBe('Mixed Wash & Fold');

    // Category services endpoint returns only services in that category
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.category-services', [$booking, $catalog['dcCat']]))
        ->assertOk()
        ->assertJsonCount(1, 'services')
        ->assertJsonPath('services.0.name', 'Suit Dry Clean');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.category-services', [$booking, $emptyCat]))
        ->assertOk()
        ->assertJsonCount(0, 'services');

    // Add item-based service with qty 5 via AJAX
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $catalog['dcService']->id,
            'item_id' => $catalog['suitItem']->id,
            'quantity' => 5,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $dcLine = $booking->operationalItems()->where('service_id', $catalog['dcService']->id)->first();
    expect($dcLine)->not->toBeNull()
        ->and($dcLine->quantity)->toBe(5)
        ->and($dcLine->line_total_minor)->toBe(150000);

    // Switch Wash & Fold without reason is rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $separateWf->id,
            'weight_kg' => 4.5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('adjustment_reason');

    // Switch Wash & Fold to Separate Wash & Fold (4.5 kg) with reason — must replace Mixed Wash & Fold, not duplicate
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $separateWf->id,
            'weight_kg' => 4.5,
            'adjustment_reason' => 'Customer requested separate wash over phone',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $wfLines = $booking->operationalItems()->where('item_category_id', $catalog['wfCat']->id)->get();
    expect($wfLines)->toHaveCount(1)
        ->and($wfLines->first()->service_id)->toBe($separateWf->id)
        ->and((float) $wfLines->first()->weight_kg)->toBe(4.5)
        ->and($wfLines->first()->line_total_minor)->toBe(202500);
});

it('displays all customer-selected category groups with empty states and prevents duplicate service additions', function () {
    $catalog = seedCatalogForBookingTests();
    $emptyCat = ItemCategory::create([
        'name' => 'Duvets & Bulky Items',
        'slug' => 'bulky-duvet-' . uniqid(),
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog, [
        'currency' => 'GBP',
        'services_snapshot' => [
            [
                'category_id' => $catalog['wfCat']->id,
                'option_service_id' => $catalog['wfService']->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold',
                'is_wash_fold' => true,
                'price' => 500.00,
                'unit' => 'kg',
            ],
            [
                'category_id' => $catalog['dcCat']->id,
                'option_service_id' => null,
                'name' => 'Dry Cleaning',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
            [
                'category_id' => $emptyCat->id,
                'option_service_id' => null,
                'name' => 'Duvets & Bulky Items',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
        ],
    ]);

    $admin = makeBookingAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Wash &amp; Fold', false)
        ->assertSee('Dry Cleaning')
        ->assertSee('Duvets &amp; Bulky Items', false)
        ->assertSee('No items added yet.')
        ->assertSee('No services configured for this category.')
        ->assertSee('Qty / Weight')
        ->assertDontSee('Qty / Weight (kg)')
        ->assertDontSee('Unit Price (BDT)')
        ->assertSee('GBP');

    // Adding Suit Dry Clean once succeeds
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $catalog['dcService']->id,
            'item_id' => $catalog['suitItem']->id,
            'quantity' => 1,
        ])
        ->assertOk();

    // Adding the exact same service_id again via addService is rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $catalog['dcService']->id,
            'item_id' => $catalog['suitItem']->id,
            'quantity' => 2,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This service has already been added.');

    // Submitting duplicate service_id via Save Booking Changes (new_items) is also rejected
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->fresh()->version,
            'new_items' => [
                'tmp_1' => [
                    'service_id' => $catalog['dcService']->id,
                    'category_id' => $catalog['dcCat']->id,
                    'quantity' => 1,
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
        ])
        ->assertSessionHasErrors('new_items.tmp_1.service_id');

    $dcLines = $booking->operationalItems()->where('service_id', $catalog['dcService']->id)->get();
    expect($dcLines)->toHaveCount(1)
        ->and($dcLines->first()->quantity)->toBe(1);
});

it('persists new_items, updated items, and deleted_item_ids atomically on Save Booking Changes', function () {
    $catalog = seedCatalogForBookingTests();
    $secondDcService = Service::create([
        'unit_id' => $catalog['itemUnit']->id,
        'item_category_id' => $catalog['dcCat']->id,
        'name' => 'Coat Dry Clean',
        'slug' => 'coat-dry-clean-' . uniqid(),
        'price' => 450.00,
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog);
    $origSnapshot = $booking->services_snapshot;
    $admin = makeBookingAdmin();

    // Initialize operational items and add one item we will later delete via deleted_item_ids
    $this->actingAs($admin, 'admin')->get(route('admin.bookings.edit', $booking))->assertOk();
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.bookings.services.add', $booking), [
            'service_id' => $catalog['dcService']->id,
            'item_id' => $catalog['suitItem']->id,
            'quantity' => 1,
        ])
        ->assertOk();

    $booking->refresh();
    $wfLine = $booking->operationalItems()->where('service_id', $catalog['wfService']->id)->firstOrFail();
    $lineToDelete = $booking->operationalItems()->where('service_id', $catalog['dcService']->id)->firstOrFail();

    // Save Booking Changes: update Wash & Fold weight to 2.5kg, add 2 new_items, delete lineToDelete
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->version,
            'items' => [
                $wfLine->id => [
                    'weight_kg' => 2.5,
                    'actual_unit_price' => 500.00,
                ],
            ],
            'new_items' => [
                'tmp_1' => [
                    'service_id' => $catalog['dcService']->id,
                    'category_id' => $catalog['dcCat']->id,
                    'item_id' => $catalog['suitItem']->id,
                    'quantity' => 3,
                    'actual_unit_price' => 300.00,
                ],
                'tmp_2' => [
                    'service_id' => $secondDcService->id,
                    'category_id' => $catalog['dcCat']->id,
                    'quantity' => 2,
                    'actual_unit_price' => 450.00,
                ],
            ],
            'deleted_item_ids' => [$lineToDelete->id],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 0,
            'tax' => 0,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking))
        ->assertSessionHas('success', 'Booking changes saved successfully.');

    $booking->refresh();

    // 2.5 * 500 (1250) + 3 * 300 (900) + 2 * 450 (900) + 50 (service fee) + 100 (delivery) = 3200 BDT = 320000 minor
    expect($booking->operationalItems()->where('id', $lineToDelete->id)->exists())->toBeFalse()
        ->and($booking->operationalItems()->count())->toBe(3)
        ->and((float) $wfLine->fresh()->weight_kg)->toBe(2.5)
        ->and($booking->final_amount_minor)->toBe(320000)
        ->and($booking->version)->toBeGreaterThan(2)
        ->and($booking->services_snapshot)->toEqual($origSnapshot)
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'item_removed')->exists())->toBeTrue()
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'service_added')->count())->toBeGreaterThanOrEqual(2)
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'admin_updated')->exists())->toBeTrue();
});

it('removes an entire category with reason, computes exact final total, restores category, and validates excessive discount', function () {
    $catalog = seedCatalogForBookingTests();
    $bulkyCat = ItemCategory::create([
        'name' => 'Bulky Item / Duvet',
        'slug' => 'bulky-item-duvet-' . uniqid(),
        'is_active' => true,
    ]);
    $wf600 = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['wfCat']->id,
        'name' => 'Mixed Wash & Fold 600',
        'slug' => 'mixed-wf-600-' . uniqid(),
        'price' => 600.00,
        'is_active' => true,
    ]);
    $expressShirt = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['dcCat']->id,
        'name' => 'Demo Express Shirt Care',
        'slug' => 'express-shirt-' . uniqid(),
        'price' => 220.00,
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog, [
        'currency' => 'BDT',
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'fee_snapshot' => [
            'service_fee' => ['type' => 'fixed', 'value' => 7, 'amount_minor' => 700],
            'collection_delivery_fee' => ['type' => 'fixed', 'value' => 40, 'amount_minor' => 4000],
        ],
        'services_snapshot' => [
            [
                'category_id' => $catalog['wfCat']->id,
                'option_service_id' => $wf600->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold 600',
                'is_wash_fold' => true,
                'price' => 600.00,
                'unit' => 'kg',
            ],
            [
                'category_id' => $catalog['dcCat']->id,
                'option_service_id' => null,
                'name' => 'Dry Cleaning',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
            [
                'category_id' => $bulkyCat->id,
                'option_service_id' => null,
                'name' => 'Bulky Item / Duvet',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
        ],
    ]);

    $origSnapshot = $booking->services_snapshot;
    $admin = makeBookingAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Remove Category')
        ->assertSee('Pending: Dry Cleaning has no selected items.');

    $wfLine = $booking->operationalItems()->where('service_id', $wf600->id)->firstOrFail();

    // Remove Bulky Item / Duvet and add Demo Express Shirt Care (1.00 kg @ 220) + Mixed WF (1.00 kg @ 600)
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->fresh()->version,
            'items' => [
                $wfLine->id => [
                    'weight_kg' => 1.00,
                    'actual_unit_price' => 600.00,
                ],
            ],
            'new_items' => [
                'tmp_1' => [
                    'service_id' => $expressShirt->id,
                    'category_id' => $catalog['dcCat']->id,
                    'weight_kg' => 1.00,
                    'actual_unit_price' => 220.00,
                ],
            ],
            'removed_categories' => [
                $bulkyCat->id => [
                    'category_id' => $bulkyCat->id,
                    'reason' => 'Customer did not include any bulky items',
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 0,
            'tax' => 0,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking))
        ->assertSessionHas('success', 'Booking changes saved successfully.');

    $booking->refresh();

    // 600 + 220 + 7 + 40 = 867.00 BDT = 86700 minor
    expect($booking->final_amount_minor)->toBe(86700)
        ->and($booking->getRemovedOperationalCategoryIds())->toContain($bulkyCat->id)
        ->and($booking->services_snapshot)->toEqual($origSnapshot)
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'category_removed')->exists())->toBeTrue();

    // Reloading edit shows Bulky Item / Duvet in Removed Operational Categories and BDT 867.00 Final Total
    $this->actingAs($admin, 'admin')->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('BDT 867.00')
        ->assertSee('Removed Operational Categories')
        ->assertSee('Restore Category');

    // Excessive discount is rejected
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->version,
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 5000.00,
            'tax' => 0,
        ])
        ->assertSessionHasErrors('discount');

    // Restore Bulky Item / Duvet
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->version,
            'restored_category_ids' => [$bulkyCat->id],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 0,
            'tax' => 0,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    $booking->refresh();
    expect($booking->getRemovedOperationalCategoryIds())->toBeEmpty()
        ->and($booking->final_amount_minor)->toBeNull()
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'category_restored')->exists())->toBeTrue();
});

it('displays consistent centralized Payment Summary with separate Payment Status and Pricing Status on Booking Details', function () {
    $catalog = seedCatalogForBookingTests();
    $wf600 = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['wfCat']->id,
        'name' => 'Mixed Wash & Fold 600',
        'slug' => 'mixed-wf-600-' . uniqid(),
        'price' => 600.00,
        'is_active' => true,
    ]);
    $expressShirt = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['dcCat']->id,
        'name' => 'Demo Express Shirt Care',
        'slug' => 'express-shirt-' . uniqid(),
        'price' => 220.00,
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog, [
        'currency' => 'BDT',
        'payment_status' => 'pending_item_count',
        'estimated_amount_minor' => 64700,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'fee_snapshot' => [
            'service_fee' => ['type' => 'fixed', 'value' => 7, 'amount_minor' => 700],
            'collection_delivery_fee' => ['type' => 'fixed', 'value' => 40, 'amount_minor' => 4000],
        ],
        'services_snapshot' => [
            [
                'category_id' => $catalog['wfCat']->id,
                'option_service_id' => $wf600->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold 600',
                'is_wash_fold' => true,
                'price' => 600.00,
                'unit' => 'kg',
            ],
            [
                'category_id' => $catalog['dcCat']->id,
                'option_service_id' => null,
                'name' => 'Dry Cleaning',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
        ],
    ]);

    $admin = makeBookingAdmin();

    // Initially Dry Cleaning has no items -> Final pricing pending + Unpaid + Awaiting Item Count
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Original Booking Estimate')
        ->assertSee('BDT 647.00')
        ->assertSee('Final pricing pending')
        ->assertSee('Unpaid')
        ->assertSee('Awaiting Weight')
        ->assertDontSee('Pending item count');

    // Save operational weights (1.00 kg @ 600 + 1.00 kg @ 220)
    $wfLine = $booking->operationalItems()->where('service_id', $wf600->id)->firstOrFail();
    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->version,
            'items' => [
                $wfLine->id => ['weight_kg' => 1.00, 'actual_unit_price' => 600.00],
            ],
            'new_items' => [
                'tmp_1' => [
                    'service_id' => $expressShirt->id,
                    'category_id' => $catalog['dcCat']->id,
                    'weight_kg' => 1.00,
                    'actual_unit_price' => 220.00,
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 0,
            'tax' => 0,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    // Booking Details now shows Items Subtotal BDT 820.00, Final Total BDT 867.00, Finalized, Unpaid
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Services &amp; Items', false)
        ->assertDontSee('Original Confirmed Snapshot')
        ->assertSee('Items Subtotal')
        ->assertSee('BDT 820.00')
        ->assertSee('BDT 867.00')
        ->assertSee('Finalized')
        ->assertSee('Unpaid')
        ->assertSee('Original Booking Estimate')
        ->assertSee('BDT 647.00');
});

it('synchronizes finalized admin operational details to customer My Orders while keeping order read-only and isolated per customer', function () {
    Mail::fake();

    $catalog = seedCatalogForBookingTests();
    $bulkyCat = ItemCategory::create([
        'name' => 'Bulky Item / Duvet',
        'slug' => 'bulky-duvet-' . uniqid(),
        'is_active' => true,
    ]);
    $wf600 = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['wfCat']->id,
        'name' => 'Mixed Wash & Fold 600',
        'slug' => 'mixed-wf-600-' . uniqid(),
        'price' => 600.00,
        'is_active' => true,
    ]);
    $expressShirt = Service::create([
        'unit_id' => $catalog['kgUnit']->id,
        'item_category_id' => $catalog['dcCat']->id,
        'name' => 'Demo Express Shirt Care',
        'slug' => 'express-shirt-' . uniqid(),
        'price' => 220.00,
        'is_active' => true,
    ]);

    $booking = makeConfirmedBooking($catalog, [
        'currency' => 'BDT',
        'payment_status' => 'pending_item_count',
        'estimated_amount_minor' => 64700,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'fee_snapshot' => [
            'service_fee' => ['type' => 'fixed', 'value' => 7, 'amount_minor' => 700],
            'collection_delivery_fee' => ['type' => 'fixed', 'value' => 40, 'amount_minor' => 4000],
        ],
        'services_snapshot' => [
            [
                'category_id' => $catalog['wfCat']->id,
                'option_service_id' => $wf600->id,
                'name' => 'Wash & Fold',
                'option_name' => 'Mixed Wash & Fold 600',
                'is_wash_fold' => true,
                'price' => 600.00,
                'unit' => 'kg',
            ],
            [
                'category_id' => $catalog['dcCat']->id,
                'option_service_id' => null,
                'name' => 'Dry Cleaning',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
            [
                'category_id' => $bulkyCat->id,
                'option_service_id' => null,
                'name' => 'Bulky Item / Duvet',
                'option_name' => null,
                'is_wash_fold' => false,
                'price' => null,
                'unit' => 'item',
            ],
        ],
    ]);

    $owner = User::findOrFail($booking->user_id);
    $otherUser = User::factory()->create();
    $admin = makeBookingAdmin();

    // Another user cannot view this order
    $this->actingAs($otherUser, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertForbidden();

    // Before finalization, customer sees Final pricing pending
    $this->actingAs($owner, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('Final pricing pending')
        ->assertSee('Pending Pricing')
        ->assertDontSee('Pending item count');

    // Admin finalizes operational items and removes Bulky Item / Duvet
    $this->actingAs($admin, 'admin')->get(route('admin.bookings.edit', $booking))->assertOk();
    $wfLine = $booking->operationalItems()->where('service_id', $wf600->id)->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.bookings.update', $booking), [
            'version' => $booking->fresh()->version,
            'items' => [
                $wfLine->id => ['weight_kg' => 1.00, 'actual_unit_price' => 600.00],
            ],
            'new_items' => [
                'tmp_1' => [
                    'service_id' => $expressShirt->id,
                    'category_id' => $catalog['dcCat']->id,
                    'weight_kg' => 1.00,
                    'actual_unit_price' => 220.00,
                ],
            ],
            'removed_categories' => [
                $bulkyCat->id => [
                    'category_id' => $bulkyCat->id,
                    'reason' => 'No bulky items received',
                ],
            ],
            'service_fee_override_mode' => 'default',
            'delivery_fee_override_mode' => 'default',
            'discount' => 0,
            'tax' => 0,
        ])
        ->assertRedirect(route('admin.bookings.edit', $booking));

    Mail::assertSent(BookingNotificationMail::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->customer_email);
    });

    // Customer now sees full finalized details matching Admin Booking Details, without removed category
    $this->actingAs($owner, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('Services &amp; Items', false)
        ->assertSee('Mixed Wash &amp; Fold 600', false)
        ->assertSee('Demo Express Shirt Care')
        ->assertSee('BDT 820.00')
        ->assertSee('BDT 867.00')
        ->assertSee('Finalized')
        ->assertSee('Payment: Unpaid')
        ->assertDontSee('Bulky Item / Duvet');

    // Customer still cannot mutate or delete confirmed booking
    $this->actingAs($owner, 'web')
        ->put(route('orders.update', $booking->reference), [])
        ->assertForbidden();
    $this->actingAs($owner, 'web')
        ->delete(route('orders.destroy', $booking->reference))
        ->assertForbidden();
});

it('supports the 11-stage operational status workflow, enforces valid transitions, logs audit history, and syncs to My Orders', function () {
    Mail::fake();

    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog, [
        'booking_status' => Booking::STATUS_PROCESSING,
        'payment_status' => 'pending',
    ]);
    $owner = User::findOrFail($booking->user_id);
    $admin = makeBookingAdmin();
    $noPermAdmin = makeBookingAdmin(['view-bookings']);

    // Show page displays all 11 statuses in Quick Status Update dropdown
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Store Assigned')
        ->assertSee('Delivery Man Assigned for Store Pickup')
        ->assertSee('On Delivery');

    // Unauthorized admin receives 403
    $this->actingAs($noPermAdmin, 'admin')
        ->post(route('admin.bookings.status.update', $booking), [
            'booking_status' => Booking::STATUS_STORE_ASSIGNED,
        ])
        ->assertForbidden();

    // Skipping directly from processing to completed is rejected
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.update', $booking), [
            'booking_status' => Booking::STATUS_COMPLETED,
        ])
        ->assertSessionHasErrors('booking_status');

    // Walk through all remaining 10 stages sequentially
    $stages = [
        Booking::STATUS_STORE_ASSIGNED,
        Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        Booking::STATUS_FINALIZED,
        Booking::STATUS_DELIVERY_MAN_PICKED_UP,
        Booking::STATUS_STORE_RECEIVED,
        Booking::STATUS_STORE_PROCESSING,
        Booking::STATUS_STORE_COMPLETE,
        Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
        Booking::STATUS_ON_DELIVERY,
        Booking::STATUS_COMPLETED,
    ];

    foreach ($stages as $stage) {
        $this->actingAs($admin, 'admin')
            ->post(route('admin.bookings.status.update', $booking), [
                'booking_status' => $stage,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($booking->fresh()->booking_status)->toBe($stage);
    }

    // Payment status remained independent ('pending' / 'Unpaid'), never auto-paid
    $booking->refresh();
    expect($booking->payment_status)->toBe('pending')
        ->and(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'status_changed')->count())->toBe(10);

    // Customer My Orders listing and details show Completed
    $this->actingAs($owner, 'web')
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Completed');

    $this->actingAs($owner, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('Completed')
        ->assertSee('Payment: Unpaid');

    Mail::assertSent(BookingNotificationMail::class, 6);
});

it('tracks verified completed operational statuses from audit history for Select2 status dropdown', function () {
    Mail::fake();

    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog, [
        'booking_status' => Booking::STATUS_PROCESSING,
    ]);
    $admin = makeBookingAdmin();

    // Initially 0 stages are completed; processing is Current
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('select2.min.css')
        ->assertSee('select2.full.min.js')
        ->assertSee('Mark Processing Done')
        ->assertSee('Step 1 of 11');

    expect($booking->getCompletedOperationalStatuses())->toBe([]);

    // Advance sequentially to store_assigned using Mark Processing Done
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_PROCESSING,
            'version' => $booking->version,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $freshBooking = Booking::findOrFail($booking->id);
    expect($freshBooking->booking_status)->toBe(Booking::STATUS_STORE_ASSIGNED)
        ->and($freshBooking->getCompletedOperationalStatuses())->toBe([Booking::STATUS_PROCESSING]);

    // Retrying with stale expected_stage=processing is rejected
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $freshBooking), [
            'expected_stage' => Booking::STATUS_PROCESSING,
        ])
        ->assertSessionHasErrors('booking_status');
});

it('advances sequentially through all 11 stages with Mark Done until 11/11 Completed and syncs to My Orders', function () {
    Mail::fake();

    $catalog = seedCatalogForBookingTests();
    $booking = makeConfirmedBooking($catalog, [
        'booking_status' => Booking::STATUS_PROCESSING,
        'payment_status' => 'pending',
    ]);
    $owner = User::findOrFail($booking->user_id);
    $admin = makeBookingAdmin();
    $noPermAdmin = makeBookingAdmin(['view-bookings']);

    // Unauthorized admin receives 403
    $this->actingAs($noPermAdmin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_PROCESSING,
        ])
        ->assertForbidden();

    // Advance through all 11 stages sequentially
    $stages = array_keys(Booking::OPERATIONAL_STATUSES);
    foreach ($stages as $idx => $expectedStage) {
        $booking->refresh();
        expect($booking->booking_status)->toBe($expectedStage);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.bookings.status.advance', $booking), [
                'expected_stage' => $expectedStage,
                'version' => $booking->version,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    $booking->refresh();
    expect($booking->booking_status)->toBe(Booking::STATUS_COMPLETED)
        ->and($booking->isOperationalWorkflowFinished())->toBeTrue()
        ->and($booking->getCompletedOperationalStatuses())->toHaveCount(11)
        ->and($booking->payment_status)->toBe('pending');

    // Further advancement after 11/11 is rejected
    $this->actingAs($admin, 'admin')
        ->post(route('admin.bookings.status.advance', $booking), [
            'expected_stage' => Booking::STATUS_COMPLETED,
        ])
        ->assertSessionHasErrors('booking_status');

    // Admin show displays 11/11 Completed and hides Mark Done
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('11/11 Completed')
        ->assertSee('All 11 operational stages completed')
        ->assertDontSee('Mark Completed Done');

    // Customer My Orders displays 11/11 Completed and independent Unpaid status
    $this->actingAs($owner, 'web')
        ->get(route('orders.show', $booking->reference))
        ->assertOk()
        ->assertSee('11/11 Completed')
        ->assertSee('Payment: Unpaid');
});
