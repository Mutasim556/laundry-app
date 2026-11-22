<?php

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Support\Facades\Gate;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'admin']);
    app(PaymentGatewaySeeder::class)->run();
});

function makeDraftSessionForImmutability(): array
{
    $unit = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_name' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::firstOrCreate(['name' => 'Wash & Fold'], ['slug' => 'wash-fold-'.uniqid(), 'is_active' => true]);
    $svc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat->id,
        'name' => 'Mixed Wash & Fold',
        'slug' => 'mixed-wash-fold-'.uniqid(),
        'price' => 600.00,
        'is_active' => true,
    ]);

    return [
        'services' => [
            [
                'category_id' => $cat->id,
                'service_id' => $svc->id,
                'option_service_id' => $svc->id,
                'name' => $cat->name,
                'option_name' => $svc->name,
                'special_request' => 'Hypoallergenic detergent please',
            ],
        ],
        'address' => [
            'collection_mode' => 'home_office',
            'address_type' => 'HOME',
            'postcode' => 'E1 1AA',
            'selected_address' => '10 DOWNING STREET, LONDON, E1 1AA',
        ],
        'schedule' => [
            'collection_date' => now()->addDay()->format('Y-m-d'),
            'collection_date_label' => 'Tomorrow',
            'collection_slot' => '09:00 - 12:00',
            'collection_instruction' => 'Ring doorbell',
            'delivery_date' => now()->addDays(2)->format('Y-m-d'),
            'delivery_date_label' => 'In 2 days',
            'delivery_slot' => '18:00 - 21:00',
            'delivery_instruction' => 'Leave at door',
        ],
        'details' => [
            'customer_type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '+44 7700900077',
        ],
    ];
}

it('generates sequential unique LND-YYYY-000001 booking references safely', function () {
    $year = now()->format('Y');
    $ref1 = Booking::generateUniqueReference();
    $ref2 = Booking::generateUniqueReference();

    expect($ref1)->toBe("LND-{$year}-000001");
    expect($ref2)->toBe("LND-{$year}-000002");
});

it('confirms a COD booking permanently, prevents duplicate bookings, renders success page, and blocks all customer modifications', function () {
    $customer = User::factory()->create();
    $session = makeDraftSessionForImmutability();

    // Confirm booking
    $resp1 = $this->actingAs($customer, 'web')
        ->withSession(['booking' => $session])
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod']);

    $booking = Booking::firstOrFail();
    $year = now()->format('Y');
    expect($booking->reference)->toStartWith("LND-{$year}-");
    expect($booking->is_locked)->toBeTrue();
    expect($booking->booking_status)->toBe(Booking::STATUS_CONFIRMED);
    expect($booking->payment_status)->toBe('pending_item_count');
    expect($booking->contact_snapshot['customer_email'])->toBe('customer@example.com');
    expect($booking->payment_snapshot['payment_method'])->toBe('cod');
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'confirmed')->exists())->toBeTrue();

    $resp1->assertRedirect(route('booking.success', $booking->reference));

    // Success page renders required locked message and navigation buttons
    $successPage = $this->actingAs($customer, 'web')->get(route('booking.success', $booking->reference));
    $successPage->assertOk();
    $successPage->assertSee('Booking Confirmed!');
    $successPage->assertSee('Your booking has been successfully received. Your order details are now locked.');
    $successPage->assertSee($booking->reference);
    $successPage->assertSee('View Booking');
    $successPage->assertSee('My Orders');
    $successPage->assertSee('Back to Home');

    // Double-click Confirm -> idempotent, no duplicate booking
    $resp2 = $this->actingAs($customer, 'web')->post(route('booking.confirm.store'), ['payment_method' => 'cod']);
    $resp2->assertRedirect(route('booking.success', $booking->reference));
    expect(Booking::count())->toBe(1);

    // Reopening Steps 2-5 as editable drafts redirects to success page
    $this->actingAs($customer, 'web')->get(route('booking.address'))->assertRedirect(route('booking.success', $booking->reference));
    $this->actingAs($customer, 'web')->get(route('booking.schedule'))->assertRedirect(route('booking.success', $booking->reference));
    $this->actingAs($customer, 'web')->get(route('booking.details'))->assertRedirect(route('booking.success', $booking->reference));
    $this->actingAs($customer, 'web')->get(route('booking.confirm'))->assertRedirect(route('booking.success', $booking->reference));

    // Attempting to modify any step via POST/AJAX returns 409 Conflict
    $this->actingAs($customer, 'web')->post(route('booking.services.store'), ['selected_services' => [1]])->assertStatus(409);
    $this->actingAs($customer, 'web')->post(route('booking.address.store'), ['collection_mode' => 'home_office'])->assertStatus(409);
    $this->actingAs($customer, 'web')->post(route('booking.schedule.store'), [])->assertStatus(409);
    $this->actingAs($customer, 'web')->post(route('booking.details.store'), [])->assertStatus(409);
    $this->actingAs($customer, 'web')->postJson(route('booking.confirm.options'), ['action' => 'update_tip', 'tip_option' => '50'])->assertStatus(409);

    // Starting a brand-new booking via ?new=1 clears the locked session
    $this->actingAs($customer, 'web')->get(route('booking.services', ['new' => 1]))->assertOk();
    expect(session('booking'))->toBeEmpty();
});

it('shows confirmed bookings in My Orders, enforces ownership and read-only policy, and displays in Admin Panel', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = Admin::create([
        'name' => 'Order Admin',
        'email' => 'orderadmin+'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo('view-payments');

    $this->actingAs($owner, 'web')
        ->withSession(['booking' => makeDraftSessionForImmutability()])
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod'])
        ->assertRedirect();

    $booking = Booking::firstOrFail();

    // Policy blocks customer update & delete
    expect(Gate::forUser($owner)->allows('view', $booking))->toBeTrue();
    expect(Gate::forUser($owner)->allows('update', $booking))->toBeFalse();
    expect(Gate::forUser($owner)->allows('delete', $booking))->toBeFalse();
    expect(Gate::forUser($otherUser)->allows('view', $booking))->toBeFalse();

    // Owner sees booking in My Orders and read-only details
    $this->actingAs($owner, 'web')->get(route('orders.index'))->assertOk()->assertSee($booking->reference);
    $this->actingAs($owner, 'web')->get(route('orders.show', $booking->reference))->assertOk()->assertSee($booking->reference);

    // Other customer cannot view, update, or delete
    $this->actingAs($otherUser, 'web')->get(route('orders.show', $booking->reference))->assertForbidden();
    $this->actingAs($owner, 'web')->put(route('orders.update', $booking->reference), [])->assertForbidden();
    $this->actingAs($owner, 'web')->delete(route('orders.destroy', $booking->reference))->assertForbidden();

    // Admin sees confirmed booking in Admin Panel
    $this->actingAs($admin, 'admin')->get(route('admin.payments.index'))->assertOk()->assertSee($booking->reference);
});

it('allows guest lookup with matching reference and email while blocking mismatched email', function () {
    $this->withSession(['booking' => makeDraftSessionForImmutability()])
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod'])
        ->assertRedirect();

    $booking = Booking::firstOrFail();

    // Flush session to simulate fresh guest browser
    $this->flushSession();

    // Direct access without lookup is forbidden
    $this->get(route('orders.show', $booking->reference))->assertForbidden();

    // Mismatched email fails
    $this->post(route('orders.lookup'), [
        'reference' => $booking->reference,
        'email' => 'wrong@example.com',
    ])->assertSessionHasErrors('reference');

    // Matching email succeeds and grants read-only access
    $this->post(route('orders.lookup'), [
        'reference' => $booking->reference,
        'email' => 'customer@example.com',
    ])->assertRedirect(route('orders.show', $booking->reference));

    $this->get(route('orders.show', $booking->reference))->assertOk()->assertSee($booking->reference);
});
