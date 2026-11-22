<?php

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;

it('auto-fills Add Address contact fields from customer profile and saves address-specific contacts without modifying User profile', function () {
    $user = User::factory()->create([
        'name' => 'Mutasim Naib',
        'email' => 'mutasim@example.com',
        'country_code' => '+880',
        'phone' => '1724698392',
        'profile_completed_at' => now(),
    ]);

    // Add Address page shows Contact Details auto-filled from profile
    $createPage = $this->actingAs($user, 'web')->get(route('account.addresses.create'));
    $createPage->assertOk();
    $createPage->assertSee('Contact Details');
    $createPage->assertSee('Add contact information for this address.');
    $createPage->assertSee('Mutasim');
    $createPage->assertSee('Naib');
    $createPage->assertSee('mutasim@example.com');

    // Save Office address with Company contact details different from main profile
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'E1 0BJ',
            'selected_address' => '100 WHITECHAPEL ROAD, LONDON, E1 0BJ',
            'address_type' => 'OFFICE',
            'label' => 'Head Office',
            'contact_type' => 'company',
            'contact_first_name' => 'Office',
            'contact_last_name' => 'Reception',
            'contact_company_name' => 'FreshGo Corporate Ltd',
            'contact_tax_number' => 'VAT-998877',
            'contact_country_iso' => 'GB',
            'contact_dialing_code' => '+44',
            'contact_phone' => '7700900111',
            'contact_email' => 'office@freshgo.test',
        ])
        ->assertRedirect(route('account.addresses'));

    $officeAddr = CustomerAddress::where('user_id', $user->id)->firstOrFail();
    expect($officeAddr->contact_type)->toBe('company');
    expect($officeAddr->contact_first_name)->toBe('Office');
    expect($officeAddr->contact_company_name)->toBe('FreshGo Corporate Ltd');
    expect($officeAddr->contact_dialing_code)->toBe('+44');
    expect($officeAddr->contact_email)->toBe('office@freshgo.test');

    // Main User profile remains untouched
    $user->refresh();
    expect($user->name)->toBe('Mutasim Naib');
    expect($user->email)->toBe('mutasim@example.com');
    expect($user->country_code)->toBe('+880');

    // Address Book list displays compact contact summary
    $this->actingAs($user, 'web')
        ->get(route('account.addresses'))
        ->assertOk()
        ->assertSee('Office Reception')
        ->assertSee('+44 7700900111');
});

it('auto-fills Booking Step 4 from selected saved address contact details, respects manual priority, and protects confirmed snapshots', function () {
    app(PaymentGatewaySeeder::class)->run();

    $user = User::factory()->create([
        'name' => 'Main Profile',
        'email' => 'main@example.com',
        'country_code' => '+44',
        'phone' => '7700900000',
        'profile_completed_at' => now(),
    ]);
    $other = User::factory()->create(['profile_completed_at' => now()]);

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

    $savedAddr = CustomerAddress::create([
        'user_id' => $user->id,
        'label' => 'Work',
        'address_type' => 'office',
        'postcode' => 'E1 1AA',
        'formatted_address' => '10 DOWNING STREET, LONDON, E1 1AA',
        'contact_type' => 'company',
        'contact_first_name' => 'Sarah',
        'contact_last_name' => 'Manager',
        'contact_company_name' => 'Acme UK Ltd',
        'contact_tax_number' => 'GB123456',
        'contact_country_iso' => 'GB',
        'contact_dialing_code' => '+44',
        'contact_phone' => '7700900222',
        'contact_email' => 'sarah@acme.test',
    ]);

    // Step 1 -> Step 2 (select saved address) -> Step 3 -> Step 4
    $this->actingAs($user, 'web')
        ->withSession([
            'booking' => [
                'services' => [[
                    'category_id' => $cat->id,
                    'service_id' => $svc->id,
                    'option_service_id' => $svc->id,
                    'name' => $cat->name,
                    'option_name' => $svc->name,
                ]],
                'schedule' => [
                    'collection_date' => now()->addDay()->format('Y-m-d'),
                    'collection_slot' => '09:00 - 12:00',
                    'delivery_date' => now()->addDays(2)->format('Y-m-d'),
                    'delivery_slot' => '18:00 - 21:00',
                ],
            ],
        ])
        ->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'E1 1AA',
            'selected_address' => '10 DOWNING STREET, LONDON, E1 1AA',
            'address_type' => 'OFFICE',
            'saved_customer_address_id' => $savedAddr->id,
        ])
        ->assertRedirect(route('booking.schedule'));

    // Step 4 auto-fills from selected saved address contact details (Sarah Manager, Acme UK Ltd, sarah@acme.test)
    $step4 = $this->actingAs($user, 'web')->get(route('booking.details'));
    $step4->assertOk();
    $step4->assertSee('Sarah');
    $step4->assertSee('Manager');
    $step4->assertSee('Acme UK Ltd');
    $step4->assertSee('sarah@acme.test');

    // Customer edits contact details during booking -> saved address is NOT modified
    $this->actingAs($user, 'web')
        ->post(route('booking.details.store'), [
            'customer_type' => 'individual',
            'first_name' => 'Custom',
            'last_name' => 'Guest',
            'country_code' => '+44',
            'country_iso' => 'GB',
            'phone_number' => '7700900333',
            'customer_email' => 'custom@example.com',
        ])
        ->assertRedirect(route('booking.confirm'));

    expect($savedAddr->fresh()->contact_first_name)->toBe('Sarah');

    // Confirm booking -> contact_snapshot frozen
    $this->actingAs($user, 'web')
        ->post(route('booking.confirm.store'), ['payment_method' => 'cod'])
        ->assertRedirect();

    $booking = Booking::firstOrFail();
    expect($booking->contact_snapshot['first_name'])->toBe('Custom');

    // Edit saved address later -> confirmed booking snapshot stays unchanged
    $this->actingAs($user, 'web')
        ->put(route('account.addresses.update', $savedAddr->id), [
            'collection_mode' => 'home_office',
            'is_manual' => '0',
            'postcode' => 'E1 1AA',
            'selected_address' => '10 DOWNING STREET, LONDON, E1 1AA',
            'address_type' => 'OFFICE',
            'contact_type' => 'individual',
            'contact_first_name' => 'Updated',
            'contact_last_name' => 'Name',
            'contact_dialing_code' => '+44',
            'contact_phone' => '7700900999',
            'contact_email' => 'updated@acme.test',
        ])
        ->assertRedirect(route('account.addresses'));

    expect($booking->fresh()->contact_snapshot['first_name'])->toBe('Custom');

    // Other customer cannot view/edit saved address
    $this->actingAs($other, 'web')->get(route('account.addresses.edit', $savedAddr->id))->assertForbidden();
});
