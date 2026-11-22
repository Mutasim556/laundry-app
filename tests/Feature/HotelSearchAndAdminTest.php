<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Hotel;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach ([
        'view-hotels',
        'create-hotels',
        'edit-hotels',
        'delete-hotels',
        'import-hotels',
        'export-hotels',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }
});

test('public hotel search returns active matching hotels for partial query and excludes inactive', function () {
    $active = Hotel::create([
        'name' => 'Grand Royal Demo Hotel',
        'address_line_1' => '100 Whitechapel Road',
        'city' => 'London',
        'postcode' => 'e11jg',
        'is_active' => true,
        'is_demo' => true,
    ]);

    $inactive = Hotel::create([
        'name' => 'Grand Inactive Hotel',
        'address_line_1' => '200 Whitechapel Road',
        'city' => 'London',
        'postcode' => 'E1 1JG',
        'is_active' => false,
        'is_demo' => true,
    ]);

    $response = $this->getJson(route('api.hotels.search', ['q' => 'GRA']));

    $response->assertOk()
        ->assertJson(['success' => true])
        ->assertJsonFragment([
            'id' => $active->id,
            'name' => 'GRAND ROYAL DEMO HOTEL',
            'postcode' => 'E1 1JG',
        ]);

    $ids = collect($response->json('hotels'))->pluck('id')->all();
    expect($ids)->toContain($active->id);
    expect($ids)->not->toContain($inactive->id);
});

test('nearby hotel search orders active hotels with coordinates by distance', function () {
    $near = Hotel::create([
        'name' => 'Near Whitechapel Hotel',
        'address_line_1' => '10 Whitechapel Road',
        'city' => 'London',
        'postcode' => 'E1 1EW',
        'latitude' => 51.5180,
        'longitude' => -0.0620,
        'is_active' => true,
    ]);

    $far = Hotel::create([
        'name' => 'Far Manchester Hotel',
        'address_line_1' => '18 London Road',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'latitude' => 53.4774,
        'longitude' => -2.2309,
        'is_active' => true,
    ]);

    $resp = $this->getJson(route('booking.hotel.search', ['lat' => 51.5182, 'lon' => -0.0628]));
    $resp->assertOk();

    $hotels = $resp->json('hotels');
    expect($hotels[0]['id'])->toBe($near->id);
    expect($hotels[1]['id'])->toBe($far->id);
});

test('booking step 2 stores selected hotel id and rejects deactivated hotel id', function () {
    $hotel = Hotel::create([
        'name' => 'Grand Central Hotel',
        'address_line_1' => '250 Euston Road',
        'city' => 'London',
        'postcode' => 'NW1 2PG',
        'is_active' => true,
    ]);

    $resp = $this->withSession([
        'booking' => [
            'services' => [['service_id' => 1, 'name' => 'Wash & Fold', 'quantity' => 1, 'price' => 100]],
        ],
    ])->post(route('booking.address.store'), [
        'collection_mode' => 'hotel',
        'hotel_id' => $hotel->id,
        'hotel_name' => $hotel->name,
        'hotel_address' => $hotel->formatted_address,
        'room_number' => '402B',
        'hotel_collection_type' => 'use_reception',
    ]);

    $resp->assertRedirect();
    expect(session('booking.address.hotel_id'))->toBe($hotel->id);
    expect(session('booking.address.room_number'))->toBe('402B');
    expect(session('booking.address.hotel_collection_type'))->toBe('use_reception');

    // Deactivate hotel; submitting with deactivated hotel_id fails validation
    $hotel->update(['is_active' => false]);

    $failResp = $this->post(route('booking.address.store'), [
        'collection_mode' => 'hotel',
        'hotel_id' => $hotel->id,
        'hotel_name' => $hotel->name,
        'hotel_address' => $hotel->formatted_address,
        'room_number' => '402B',
        'hotel_collection_type' => 'use_reception',
    ]);

    $failResp->assertSessionHasErrors(['hotel_name']);
});

test('manual hotel entry works without creating global hotel record', function () {
    $beforeCount = Hotel::count();

    $resp = $this->withSession([
        'booking' => [
            'services' => [['service_id' => 1, 'name' => 'Wash & Fold', 'quantity' => 1, 'price' => 100]],
        ],
    ])->post(route('booking.address.store'), [
        'collection_mode' => 'hotel',
        'hotel_name' => 'Custom Boutique Inn',
        'hotel_address' => '15 Baker Street, London, W1U 8EW',
        'room_number' => '12',
        'hotel_collection_type' => 'meet_driver',
    ]);

    $resp->assertRedirect();
    expect(Hotel::count())->toBe($beforeCount);
    expect(session('booking.address.hotel_id'))->toBeNull();
    expect(session('booking.address.hotel_name'))->toBe('CUSTOM BOUTIQUE INN');
});

test('unauthenticated and unauthorized admins cannot manage hotels', function () {
    $this->get(route('admin.hotels.index'))->assertRedirect(route('admin.login'));

    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin')->get(route('admin.hotels.index'))->assertStatus(403);
});

test('authorized admin can add, edit, toggle status, and delete hotels', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-hotels', 'create-hotels', 'edit-hotels', 'delete-hotels']);

    $create = $this->actingAs($admin, 'admin')->postJson(route('admin.hotels.store'), [
        'name' => 'Mayfair Demo Suites',
        'address_line_1' => '10 Mount Street',
        'city' => 'London',
        'postcode' => 'w1k2th',
        'is_active' => 1,
    ]);

    $create->assertCreated()->assertJson(['success' => true]);
    $id = $create->json('hotel.id');

    // Duplicate prevention
    $this->actingAs($admin, 'admin')->postJson(route('admin.hotels.store'), [
        'name' => 'Mayfair Demo Suites',
        'address_line_1' => '10 Mount Street',
        'city' => 'London',
        'postcode' => 'W1K 2TH',
    ])->assertStatus(422);

    // Search finds new hotel
    $this->getJson(route('api.hotels.search', ['q' => 'Mayfair']))
        ->assertOk()
        ->assertJsonFragment(['id' => $id]);

    // Edit hotel
    $this->actingAs($admin, 'admin')->putJson(route('admin.hotels.update', $id), [
        'name' => 'Mayfair Updated Suites',
        'address_line_1' => '12 Mount Street',
        'city' => 'London',
        'postcode' => 'W1K 2TH',
        'is_active' => 1,
    ])->assertOk();

    $this->getJson(route('api.hotels.search', ['q' => 'Mayfair Updated']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'MAYFAIR UPDATED SUITES']);

    // Deactivate hotel
    $this->actingAs($admin, 'admin')->patchJson(route('admin.hotels.toggle-status', $id))
        ->assertOk()
        ->assertJson(['is_active' => false]);

    $afterDeactivate = $this->getJson(route('api.hotels.search', ['q' => 'Mayfair Updated']));
    expect(collect($afterDeactivate->json('hotels'))->pluck('id')->all())->not->toContain($id);

    // Delete hotel
    $this->actingAs($admin, 'admin')->deleteJson(route('admin.hotels.destroy', $id))->assertOk();
    expect(Hotel::find($id))->toBeNull();
});

test('authorized admin can import and export hotels via csv', function () {
    Storage::fake('local');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-hotels', 'import-hotels', 'export-hotels']);

    $csv = implode("\n", [
        'name,address_line_1,city,postcode',
        'Soho Demo Hotel,50 Dean Street,London,w1d5bg',
        'Broken Row,No Postcode,London,INVALID',
    ]);

    $file = UploadedFile::fake()->createWithContent('hotels.csv', $csv);

    $preview = $this->actingAs($admin, 'admin')->postJson(route('admin.hotels.import.preview'), ['csv_file' => $file]);
    $preview->assertOk();

    $import = $this->actingAs($admin, 'admin')->postJson(route('admin.hotels.import.process'), [
        'import_token' => $preview->json('import_token'),
        'duplicate_strategy' => 'skip',
        'mark_as_demo' => true,
        'mapping' => ['name' => 0, 'address_line_1' => 1, 'city' => 2, 'postcode' => 3],
    ]);

    $import->assertOk()->assertJson([
        'success' => true,
        'summary' => ['imported' => 1, 'failed' => 1],
    ]);

    expect(Hotel::where('postcode', 'W1D 5BG')->exists())->toBeTrue();

    $export = $this->actingAs($admin, 'admin')->get(route('admin.hotels.export'));
    $export->assertOk();
    expect($export->headers->get('content-type'))->toContain('text/csv');
});
