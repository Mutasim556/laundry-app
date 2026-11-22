<?php

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\User;

it('renders the dedicated Add Address page matching Booking Step 2 without booking progress bar or Next button', function () {
    $user = User::factory()->create(['profile_completed_at' => now()]);

    $resp = $this->actingAs($user, 'web')->get(route('account.addresses.create'));

    $resp->assertOk();
    $resp->assertSee('Add your address');
    $resp->assertSee('Enter your address so the driver can find you.');
    $resp->assertSee('Enter UK Postcode');
    $resp->assertSee('Find Address');
    $resp->assertSee('Select Your Address');
    $resp->assertSee("My address isn't listed", false);
    $resp->assertSee("Don't know your postcode? Enter address manually", false);
    $resp->assertSee('Search for your hotel');
    $resp->assertSee('Address Label');
    $resp->assertSee('Set as default address');
    $resp->assertSee('Save Address');
    $resp->assertDontSee('Step 2 of Booking');
    $resp->assertDontSee('Selected Services');
});

it('saves postcode, manual, and hotel addresses via the shared Step 2 form and supports editing without duplicates', function () {
    $user = User::factory()->create(['profile_completed_at' => now()]);
    $other = User::factory()->create(['profile_completed_at' => now()]);

    // 1. Save UK Postcode address (OFFICE)
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'e1 1aa',
            'selected_address' => '10 DOWNING STREET, LONDON, E1 1AA',
            'address_type' => 'OFFICE',
            'address_details' => '2nd Floor Reception',
            'label' => 'Work',
            'is_default' => '1',
        ])
        ->assertRedirect(route('account.addresses'));

    $officeAddr = CustomerAddress::where('user_id', $user->id)->firstOrFail();
    expect($officeAddr->address_type)->toBe('office');
    expect($officeAddr->postcode)->toBe('E1 1AA');
    expect($officeAddr->formatted_address)->toBe('10 DOWNING STREET, LONDON, E1 1AA');
    expect($officeAddr->label)->toBe('Work');
    expect($officeAddr->is_default)->toBeTrue();

    // 2. Save Manual address without postcode
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'collection_mode' => 'home_office',
            'is_manual' => '1',
            'manual_address' => 'Flat 64, Brockmer House, London',
            'address_type' => 'HOME',
            'address_details' => 'Ring buzzer 64',
            'label' => 'Home Flat',
        ])
        ->assertRedirect(route('account.addresses'));

    expect(CustomerAddress::where('user_id', $user->id)->count())->toBe(2);

    // 3. Save Hotel address
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'collection_mode' => 'hotel',
            'address_type' => 'HOTEL',
            'hotel_name' => 'The Savoy London',
            'hotel_address' => 'Strand, London, WC2R 0EZ',
            'room_number' => '522',
            'hotel_collection_type' => 'use_reception',
            'address_details' => 'Leave with concierge',
            'label' => 'London Hotel',
        ])
        ->assertRedirect(route('account.addresses'));

    $hotelAddr = CustomerAddress::where('user_id', $user->id)->where('address_type', 'hotel')->firstOrFail();
    expect($hotelAddr->hotel_name)->toBe('THE SAVOY LONDON');
    expect($hotelAddr->room_number)->toBe('522');
    expect($hotelAddr->hotel_collection_type)->toBe('use_reception');

    // 4. Edit page prepopulates saved values and updates without creating a duplicate
    $editPage = $this->actingAs($user, 'web')->get(route('account.addresses.edit', $hotelAddr->id));
    $editPage->assertOk();
    $editPage->assertSee('THE SAVOY LONDON');
    $editPage->assertSee('Update Address');

    $this->actingAs($user, 'web')
        ->put(route('account.addresses.update', $hotelAddr->id), [
            'collection_mode' => 'hotel',
            'address_type' => 'HOTEL',
            'hotel_name' => 'The Savoy London',
            'hotel_address' => 'Strand, London, WC2R 0EZ',
            'room_number' => '700',
            'hotel_collection_type' => 'meet_driver',
            'label' => 'Savoy Suite',
        ])
        ->assertRedirect(route('account.addresses'));

    expect(CustomerAddress::where('user_id', $user->id)->count())->toBe(3);
    $hotelAddr->refresh();
    expect($hotelAddr->room_number)->toBe('700');
    expect($hotelAddr->hotel_collection_type)->toBe('meet_driver');

    // 5. Other customer cannot view or edit
    $this->actingAs($other, 'web')->get(route('account.addresses.edit', $hotelAddr->id))->assertForbidden();

    // 6. Confirmed booking snapshot remains unchanged
    $booking = Booking::create([
        'reference' => 'LND-2026-000888',
        'user_id' => $user->id,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'customer_phone' => '+44 7700900077',
        'address_snapshot' => ['selected_address' => '10 DOWNING STREET, LONDON, E1 1AA'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [['name' => 'Wash & Fold']],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending_item_count',
        'booking_status' => 'confirmed',
        'is_locked' => true,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user, 'web')->delete(route('account.addresses.destroy', $officeAddr->id))->assertRedirect(route('account.addresses'));
    expect($booking->fresh()->address_snapshot['selected_address'])->toBe('10 DOWNING STREET, LONDON, E1 1AA');
});
