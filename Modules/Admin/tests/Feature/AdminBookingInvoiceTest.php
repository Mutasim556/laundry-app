<?php

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingOperationalItem;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
});

function createInvoiceTestBooking(bool $finalizedStageDone = true, string $currency = 'GBP'): Booking
{
    $store = Store::create([
        'name' => 'Piccadilly Flagship Store',
        'slug' => 'piccadilly-flagship-' . random_int(1000, 9999),
        'code' => 'STR-' . random_int(1000, 9999),
        'email' => 'store' . random_int(1000, 9999) . '@example.com',
        'phone' => '+441614960099',
        'address_line_1' => '18 Piccadilly',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
    ]);

    $completed = [
        Booking::STATUS_PROCESSING => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_STORE_ASSIGNED => ['completed_at' => now()->toIso8601String()],
        Booking::STATUS_DELIVERY_MAN_ASSIGNED => ['completed_at' => now()->toIso8601String()],
    ];

    if ($finalizedStageDone) {
        $completed[Booking::STATUS_FINALIZED] = ['completed_at' => now()->toIso8601String()];
    }

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Invoice Test Customer',
        'customer_email' => 'invoice-customer@example.com',
        'customer_phone' => '+447700900555',
        'customer_type' => 'registered',
        'address_snapshot' => [
            'full_address' => '45 Deansgate, Manchester, M1 2PF',
            'postcode' => 'M1 2PF',
        ],
        'schedule_snapshot' => [
            'collection_date' => '2026-11-22',
            'collection_slot' => '20:00 - 23:00',
            'delivery_date' => '2026-11-23',
            'delivery_slot' => '20:00 - 23:00',
        ],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => $finalizedStageDone ? Booking::STATUS_DELIVERY_MAN_PICKED_UP : Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'assigned_store_id' => $store->id,
        'completed_operational_stages' => $completed,
        'currency' => $currency,
        'estimated_amount_minor' => 64700,
        'service_fee_minor' => 700,
        'collection_delivery_fee_minor' => 4000,
        'tax_minor' => 0,
        'discount_minor' => 0,
        'final_amount_minor' => 79700,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '18 Piccadilly, Manchester',
        'store_city_snapshot' => 'Manchester',
        'store_postcode_snapshot' => 'M1 2PF',
        'store_phone_snapshot' => $store->phone,
        'customer_postcode_snapshot' => 'M1 2PF',
        'postcode_matched' => true,
        'is_current' => true,
        'assigned_at' => now(),
    ]);

    $cat = ItemCategory::create([
        'name' => 'Wash & Fold',
        'slug' => 'wash-fold-' . random_int(1000, 9999),
        'is_active' => true,
    ]);

    $service = Service::create([
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold-' . random_int(1000, 9999),
        'pricing_mode' => 'weight',
        'price_minor' => 60000,
        'is_active' => true,
    ]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $service->id,
        'item_category_id' => $cat->id,
        'service_name' => 'Mixed Wash & Fold',
        'category_name' => 'Wash & Fold',
        'pricing_mode' => 'weight',
        'weight_kg' => 1.25,
        'catalog_unit_price_minor' => 60000,
        'actual_unit_price_minor' => 60000,
        'line_total_minor' => 75000,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return $booking;
}

test('hides Print Invoice button and rejects direct invoice access before Finalized stage is completed', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createInvoiceTestBooking(finalizedStageDone: false);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertStatus(200)
        ->assertDontSee('btnOpenPrintInvoiceModal', false);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.invoice.preview', $booking))
        ->assertStatus(403);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.invoice.print', $booking))
        ->assertStatus(403);
});

test('renders Thermal (58mm/80mm) and A4 invoices with authoritative pricing after Finalized stage is completed', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createInvoiceTestBooking(finalizedStageDone: true, currency: 'GBP');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertStatus(200)
        ->assertSee('btnOpenPrintInvoiceModal', false)
        ->assertSee('Print Invoice');

    // Thermal 80mm preview
    $thermalRes = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.invoice.preview', [$booking, 'format' => 'thermal', 'paper_size' => '80mm']))
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('format', 'thermal')
        ->assertJsonPath('paper_size', '80mm');

    $invoiceNumber = $thermalRes->json('invoice_number');
    expect($invoiceNumber)->toStartWith('INV-');
    expect($thermalRes->json('html'))
        ->toContain('thermal-receipt--80mm')
        ->toContain('Mixed Wash &amp; Fold')
        ->toContain('GBP 750.00')
        ->toContain('GBP 797.00')
        ->not->toContain('GBP 647.00'); // Must not print Original Booking Estimate

    // Thermal 58mm print route
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.invoice.print', [$booking, 'format' => 'thermal', 'paper_size' => '58mm']))
        ->assertStatus(200)
        ->assertSee('size: 58mm auto;', false)
        ->assertSee('thermal-receipt--58mm', false)
        ->assertSee($invoiceNumber);

    // A4 print route
    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.invoice.print', [$booking, 'format' => 'a4']))
        ->assertStatus(200)
        ->assertSee('size: A4 portrait;', false)
        ->assertSee('INVOICE')
        ->assertSee('Piccadilly Flagship Store')
        ->assertSee('GBP 797.00')
        ->assertSee($invoiceNumber);
});

test('retains stable invoice number on repeated prints and creates new revision when pricing changes', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-bookings', 'manage-bookings']);

    $booking = createInvoiceTestBooking(finalizedStageDone: true, currency: 'BDT');

    $first = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.invoice.preview', $booking))
        ->assertStatus(200)
        ->json('invoice_number');

    $second = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.invoice.preview', [$booking, 'format' => 'a4']))
        ->assertStatus(200)
        ->json('invoice_number');

    expect($second)->toBe($first)
        ->and(BookingInvoice::where('booking_id', $booking->id)->count())->toBe(1);

    // Authorized pricing revision creates -R2 and preserves prior record
    $booking->update(['discount_minor' => 5000]);

    $third = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.bookings.invoice.preview', $booking))
        ->assertStatus(200)
        ->assertJsonPath('revision_number', 2)
        ->json('invoice_number');

    expect($third)->toBe($first . '-R2')
        ->and(BookingInvoice::where('booking_id', $booking->id)->count())->toBe(2)
        ->and(BookingInvoice::where('booking_id', $booking->id)->where('is_current', false)->value('invoice_number'))->toBe($first);
});
