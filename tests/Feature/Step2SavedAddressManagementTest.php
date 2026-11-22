<?php

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\User;

it('shows login prompt for guests on Step 2 and saved address management for authenticated customers', function () {
    $bookingDraft = [
        'services' => [['category_id' => 1, 'service_id' => 1, 'name' => 'Wash & Fold']],
    ];

    // Guest view
    $guestResp = $this->withSession(['booking' => $bookingDraft])->get(route('booking.address'));
    $guestResp->assertOk();
    $guestResp->assertSee('to view or save your addresses.');
    $guestResp->assertDontSee('+ Add Address');

    // Authenticated customer view
    $user = User::factory()->create(['profile_completed_at' => now()]);
    $addr = CustomerAddress::create([
        'user_id' => $user->id,
        'label' => 'My Home',
        'address_type' => 'home',
        'postcode' => 'E1 1AA',
        'formatted_address' => '10 DOWNING STREET, LONDON, E1 1AA',
        'is_default' => true,
    ]);

    $authResp = $this->actingAs($user, 'web')
        ->withSession(['booking' => $bookingDraft])
        ->get(route('booking.address'));

    $authResp->assertOk();
    $authResp->assertSee('+ Add Address');
    $authResp->assertSee('My Home');
    $authResp->assertSee('10 DOWNING STREET, LONDON, E1 1AA');
    $authResp->assertSee('Default');
    $authResp->assertSee('Remove this saved address?');
});

it('adds and edits saved addresses from Step 2, auto-selects them, preserves booking services, and handles AJAX removal safely', function () {
    $user = User::factory()->create(['profile_completed_at' => now()]);
    $other = User::factory()->create(['profile_completed_at' => now()]);
    $bookingDraft = [
        'services' => [['category_id' => 1, 'service_id' => 2, 'name' => 'Wash & Fold']],
    ];

    // 1. Add new Home address from Step 2 (return_to=booking_address)
    $this->actingAs($user, 'web')
        ->withSession(['booking' => $bookingDraft])
        ->post(route('account.addresses.store'), [
            'return_to' => 'booking_address',
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'E1 0BJ',
            'selected_address' => '42 WHITECHAPEL ROAD, LONDON, E1 0BJ',
            'address_type' => 'HOME',
            'address_details' => 'Flat 3',
            'label' => 'East London',
            'is_default' => '1',
        ])
        ->assertRedirect(route('booking.address'));

    $saved = CustomerAddress::where('user_id', $user->id)->firstOrFail();
    expect(session('booking.services'))->toBe($bookingDraft['services']);
    expect((int) session('booking.address.saved_customer_address_id'))->toBe($saved->id);
    expect(session('booking.address.selected_address'))->toBe('42 WHITECHAPEL ROAD, LONDON, E1 0BJ');

    // 2. Edit the saved address from Step 2 -> returns to booking.address and refreshes session
    $this->actingAs($user, 'web')
        ->put(route('account.addresses.update', $saved->id), [
            'return_to' => 'booking_address',
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'E1 0BJ',
            'selected_address' => '44 WHITECHAPEL ROAD, LONDON, E1 0BJ',
            'address_type' => 'OFFICE',
            'label' => 'East Office',
        ])
        ->assertRedirect(route('booking.address'));

    expect(session('booking.address.selected_address'))->toBe('44 WHITECHAPEL ROAD, LONDON, E1 0BJ');
    expect(session('booking.address.address_type'))->toBe('OFFICE');

    // 3. Add a Hotel address too (becomes second address)
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'return_to' => 'booking_address',
            'collection_mode' => 'hotel',
            'address_type' => 'HOTEL',
            'hotel_name' => 'The Savoy',
            'hotel_address' => 'Strand, London',
            'room_number' => '305',
            'hotel_collection_type' => 'use_reception',
        ])
        ->assertRedirect(route('booking.address'));

    $hotelSaved = CustomerAddress::where('user_id', $user->id)->where('address_type', 'hotel')->firstOrFail();
    expect(session('booking.address.room_number'))->toBe('305');
    expect(session('booking.address.hotel_collection_type'))->toBe('use_reception');

    // 4. Confirmed historical booking snapshot is preserved even after deleting saved address
    $confirmedBooking = Booking::create([
        'reference' => 'LND-2026-000654',
        'user_id' => $user->id,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'customer_phone' => '+44 7700900077',
        'address_snapshot' => ['selected_address' => 'STRAND, LONDON'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [['name' => 'Wash & Fold']],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending_item_count',
        'booking_status' => 'confirmed',
        'is_locked' => true,
        'confirmed_at' => now(),
    ]);

    // 5. Other customer cannot delete
    $this->actingAs($other, 'web')
        ->deleteJson(route('account.addresses.destroy', $hotelSaved->id))
        ->assertForbidden();

    // 6. Owner removes currently selected saved address via AJAX -> clears selection, preserves services & historical bookings
    $delResp = $this->actingAs($user, 'web')
        ->deleteJson(route('account.addresses.destroy', $hotelSaved->id));

    $delResp->assertOk()->assertJson([
        'success' => true,
        'cleared_selected' => true,
    ]);
    expect(session('booking.address'))->toBeNull();
    expect(session('booking.services'))->toBe($bookingDraft['services']);
    expect($confirmedBooking->fresh()->address_snapshot['selected_address'])->toBe('STRAND, LONDON');
});
