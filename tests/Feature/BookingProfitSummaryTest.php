<?php

use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreEarning;
use App\Models\User;
use App\Services\BookingProfitService;
use Modules\Admin\Models\Admin;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

function makeProfitTestBooking(array $overrides = []): Booking
{
    $user = User::factory()->create();

    return Booking::create(array_merge([
        'reference' => 'LND-2026-' . rand(100000, 999999),
        'user_id' => $user->id,
        'customer_type' => 'individual',
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'customer_phone' => '+447700900111',
        'postcode' => 'M1 2PF',
        'selected_address' => '18 London Road',
        'address_type' => 'HOME',
        'collection_date' => now()->addDay()->toDateString(),
        'collection_slot' => '10:00 - 12:00',
        'delivery_date' => now()->addDays(2)->toDateString(),
        'delivery_slot' => '14:00 - 16:00',
        'payment_gateway' => 'stripe',
        'payment_status' => 'pending',
        'booking_status' => Booking::STATUS_PROCESSING,
        'currency' => 'GBP',
        'estimated_amount_minor' => 5000,
        'final_amount_minor' => 5000,
        'service_fee_minor' => 500,
        'collection_delivery_fee_minor' => 1000,
        'confirmed_at' => now(),
        'address_snapshot' => ['selected_address' => '18 London Road', 'postcode' => 'M1 2PF', 'address_type' => 'HOME'],
        'schedule_snapshot' => ['collection_date' => now()->addDay()->toDateString(), 'collection_slot' => '10:00 - 12:00'],
        'services_snapshot' => [['name' => 'Wash & Fold']],
        'details_snapshot' => ['customer_name' => $user->name, 'customer_email' => $user->email],
        'pricing_snapshot' => ['currency' => 'GBP', 'estimated_total' => 50.00],
        'snapshot_hash' => hash('sha256', uniqid('snap', true)),
    ], $overrides));
}

function attachOperationalItem(Booking $booking, int $lineTotalMinor = 3500): void
{
    $unit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-' . uniqid(), 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wf-' . uniqid(),
        'price' => $lineTotalMinor / 100,
        'is_active' => true,
    ]);

    BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'item_category_id' => $cat->id,
        'service_id' => $svc->id,
        'category_name' => 'Wash & Fold',
        'service_name' => 'Mixed Wash & Fold',
        'unit_name' => 'kg',
        'pricing_mode' => 'weight',
        'quantity' => 1.00,
        'weight_kg' => 1.00,
        'catalog_unit_price_minor' => $lineTotalMinor,
        'actual_unit_price_minor' => $lineTotalMinor,
        'line_total_minor' => $lineTotalMinor,
    ]);
}

it('shows available revenue and missing cost notice when no store is assigned or store commission is null', function () {
    $booking = makeProfitTestBooking();
    attachOperationalItem($booking, 3500);

    $summary = app(BookingProfitService::class)->calculateForBooking($booking);

    expect($summary['has_any_financial_data'])->toBeTrue();
    expect($summary['has_provisional_estimate'])->toBeTrue();
    expect($summary['has_store_basis'])->toBeFalse();
    expect($summary['status_label'])->toBe('Provisional (Incomplete Costs)');
    expect($summary['recognized_net_revenue_minor'])->toBe(5000);
    expect($summary['formatted']['store_net_earnings'])->toBe('Not recorded');
    expect($summary['missing_components'])->not->toBeEmpty();

    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Profit Admin',
        'email' => 'profit-admin-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->givePermissionTo('view-bookings');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Booking Revenue')
        ->assertSee('Total Direct Costs')
        ->assertSee('Not recorded')
        ->assertSee('Missing / Pending Cost Components');
});

it('calculates finalized profit, handles negative contribution, and returns N/A margin on zero revenue', function () {
    $booking = makeProfitTestBooking();
    attachOperationalItem($booking, 10000);

    $store = Store::create([
        'store_code' => 'STR-PRF-' . uniqid(),
        'ownership_type' => 'vendor_store',
        'status' => 'active',
        'name' => 'Profit Store',
        'slug' => 'profit-store-' . uniqid(),
        'contact_person' => 'Manager',
        'phone' => '07700900002',
        'email' => 'pstore' . uniqid() . '@example.com',
        'store_type' => 'both',
        'postcode' => 'M1 2PF',
        'commission_rate' => 20.00,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '10 High Street, M1 2PF',
        'store_postcode_snapshot' => 'M1 2PF',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    BookingStoreEarning::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'currency' => 'GBP',
        'eligible_service_amount_minor' => 10000,
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
        'commission_amount_minor' => 2000,
        'net_store_earnings_minor' => 8000,
        'is_current' => true,
        'snapshot_hash' => hash('sha256', uniqid('earn', true)),
        'finalized_at' => now(),
    ]);

    $booking->forceFill([
        'completed_operational_stages' => [
            Booking::STATUS_FINALIZED => ['completed_at' => now()->toIso8601String()],
        ],
    ])->save();

    $svc = app(BookingProfitService::class);
    $finalized = $svc->calculateForBooking($booking);

    expect($finalized['is_finalized'])->toBeTrue();
    expect($finalized['status_label'])->toBe('Finalized Booking Profit');
    expect($finalized['recognized_net_revenue_minor'])->toBe(11500); // 10000 + 500 + 1000
    expect($finalized['store_net_earnings_minor'])->toBe(8000);

    // Negative contribution when discount exceeds revenue
    $booking->forceFill(['discount_minor' => 15000])->save();
    $negative = $svc->calculateForBooking($booking->fresh());
    expect($negative['total_profit_minor'])->toBeLessThan(0);

    // Zero revenue case -> N/A margin & Incomplete Financial Data
    $zeroBooking = makeProfitTestBooking([
        'estimated_amount_minor' => 0,
        'final_amount_minor' => 0,
        'service_fee_minor' => 0,
        'collection_delivery_fee_minor' => 0,
    ]);
    $zeroSummary = $svc->calculateForBooking($zeroBooking);
    expect($zeroSummary['margin_percentage_formatted'])->toBe('N/A');
    expect($zeroSummary['has_any_financial_data'])->toBeFalse();
    expect($zeroSummary['status_label'])->toBe('Incomplete Financial Data');
});
