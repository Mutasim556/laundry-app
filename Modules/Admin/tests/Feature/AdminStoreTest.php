<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;
use Modules\Admin\Models\Upazila;
use Modules\Admin\Models\Vendor;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-stores', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-stores', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'edit-stores', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-stores', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-vendors', 'guard_name' => 'admin']);

    Storage::fake('public');

    $this->division = Division::firstOrCreate(
        ['slug' => 'dhaka'],
        ['name' => 'Dhaka', 'bn_name' => 'ঢাকা', 'is_active' => true]
    );

    $this->district = District::firstOrCreate(
        ['division_id' => $this->division->id, 'slug' => 'dhaka'],
        ['name' => 'Dhaka', 'bn_name' => 'ঢাকা', 'is_active' => true]
    );

    $this->upazila = Upazila::firstOrCreate(
        ['district_id' => $this->district->id, 'slug' => 'dhanmondi'],
        ['name' => 'Dhanmondi', 'bn_name' => 'ধানমন্ডি', 'is_active' => true]
    );

    $this->localArea = LocalArea::firstOrCreate(
        ['upazila_id' => $this->upazila->id, 'slug' => 'dhanmondi-ra'],
        ['name' => 'Dhanmondi R/A', 'bn_name' => 'ধানমন্ডি আ/এ', 'postcode' => '1205', 'is_active' => true]
    );
});

test('unauthenticated users cannot view stores index', function () {
    $response = $this->get(route('admin.stores.index'));
    $response->assertRedirect(route('admin.login'));
});

test('admin with view-stores permission can view stores index', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-stores');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.stores.index'));
    $response->assertStatus(200);
    $response->assertSee('Store Management');
});

test('admin can retrieve stores json data for datatable', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-stores');

    Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Dhanmondi Central Wash',
        'slug' => 'dhanmondi-central-wash',
        'store_type' => 'both',
        'phone' => '01711000000',
        'full_address' => 'House 12, Road 5, Dhanmondi, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'delivery_fee' => 40.00,
        'min_order_amount' => 120.00,
        'has_delivery' => true,
        'is_accepting_orders' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.data'));
    $response->assertStatus(200)
             ->assertJsonStructure(['data' => [['id', 'name', 'ownership_type', 'division_id', 'full_address']]]);
});

test('admin can check and generate unique slug for store', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-stores');

    Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Uttara Hub',
        'slug' => 'uttara-hub',
        'store_type' => 'laundry',
        'phone' => '01711000001',
        'full_address' => 'Sector 3, Uttara, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.check-slug', [
        'name' => 'Uttara Hub',
    ]));

    $response->assertStatus(200)
             ->assertJson(['slug' => 'uttara-hub-1']);
});

test('admin can query linked bangladesh locations dynamically', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-stores');

    // Query districts by division
    $responseDist = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.geo.districts', [
        'division_id' => $this->division->id,
    ]));
    $responseDist->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Dhaka']);

    // Query upazilas by district
    $responseUpa = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.geo.upazilas', [
        'district_id' => $this->district->id,
    ]));
    $responseUpa->assertStatus(200)
                ->assertJsonFragment(['name' => 'Dhanmondi']);

    // Query local areas by upazila
    $responseArea = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.geo.local-areas', [
        'upazila_id' => $this->upazila->id,
    ]));
    $responseArea->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Dhanmondi R/A', 'postcode' => '1205']);
});

test('admin can create vendor and vendor-owned store via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-stores');
    $admin->givePermissionTo('manage-vendors');

    // 1. Create Vendor
    $vendorRes = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.vendors.store'), [
        'name' => 'CleanPro Bangladesh',
        'owner_name' => 'Shahidul Alam',
        'phone' => '01811223344',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 12.50,
        'payout_method' => 'bKash',
        'payout_details' => '01811223344',
    ]);

    $vendorRes->assertStatus(201);
    $vendorId = $vendorRes->json('vendor.id');
    expect($vendorId)->not->toBeNull();

    // 2. Create Store owned by this vendor
    $logo = UploadedFile::fake()->image('store_logo.png');

    $storeRes = $this->actingAs($admin, 'admin')->post(route('admin.stores.store'), [
        'ownership_type' => 'vendor_store',
        'vendor_id' => $vendorId,
        'status' => 'active',
        'name' => 'CleanPro Banani Express',
        'bn_name' => 'ক্লিনপ্রো বনানী এক্সপ্রেস',
        'slug' => 'cleanpro-banani-express',
        'store_type' => 'both',
        'phone' => '01811223344',
        'holding_no' => 'Plot 88',
        'road_no' => 'Road 11',
        'nearby_landmark' => 'Near Banani Star Kebab',
        'full_address' => 'Plot 88, Road 11, Block D, Banani, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'has_delivery' => '1',
        'delivery_fee' => '60.00',
        'min_order_amount' => '200.00',
        'service_areas_text' => 'Banani, Gulshan 1, Gulshan 2, Mohakhali DOHS',
        'turnaround_time' => '24 Hours',
        'logo' => $logo,
    ], ['Accept' => 'application/json']);

    $storeRes->assertStatus(201);
    $store = Store::where('slug', 'cleanpro-banani-express')->first();
    expect($store)->not->toBeNull();
    expect($store->vendor_id)->toBe($vendorId);
    expect($store->ownership_type)->toBe('vendor_store');
    expect((float) $store->delivery_fee)->toBe(60.00);
    expect($store->logo)->not->toBeNull();
    Storage::disk('public')->assertExists($store->logo);
});

test('admin can update store and toggle accepting orders', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-stores');

    $store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Mirpur Wash Station',
        'slug' => 'mirpur-wash-station',
        'store_type' => 'laundry',
        'phone' => '01911000000',
        'full_address' => 'Mirpur 10, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'is_accepting_orders' => true,
    ]);

    // Update
    $updateRes = $this->actingAs($admin, 'admin')->put(route('admin.stores.update', $store), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Mirpur Wash Station Updated',
        'slug' => 'mirpur-wash-station-updated',
        'store_type' => 'both',
        'phone' => '01911999999',
        'full_address' => 'Mirpur 10 Circle, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'has_delivery' => '1',
        'delivery_fee' => '45.00',
    ], ['Accept' => 'application/json']);

    $updateRes->assertStatus(200);
    $store->refresh();
    expect($store->name)->toBe('Mirpur Wash Station Updated');
    expect($store->phone)->toBe('01911999999');

    // Toggle Order Acceptance
    $toggleRes = $this->actingAs($admin, 'admin')->patchJson(route('admin.stores.toggle-orders', $store));
    $toggleRes->assertStatus(200)
              ->assertJson(['is_accepting_orders' => false]);
    expect($store->fresh()->is_accepting_orders)->toBeFalse();
});

test('admin can delete store', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('delete-stores');

    $logo = UploadedFile::fake()->image('delete_logo.png');
    $path = $logo->store('stores/logos', 'public');

    $store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Temporary Branch',
        'slug' => 'temp-branch',
        'store_type' => 'laundry',
        'phone' => '01511000000',
        'full_address' => 'Baridhara, Dhaka',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'logo' => $path,
    ]);

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.stores.destroy', $store));
    $response->assertStatus(200);

    expect(Store::where('id', $store->id)->exists())->toBeFalse();
});


test('uk postcodes endpoint returns distinct normalized sorted postcodes from uk_addresses', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-stores');

    UkAddress::create([
        'postcode' => 'm1  2pf',
        'postcode_compact' => 'M12PF',
        'address_line_1' => '1 Piccadilly Gardens',
        'street' => 'Piccadilly Gardens',
        'city' => 'Manchester',
        'is_active' => true,
    ]);
    UkAddress::create([
        'postcode' => 'M1 2PF',
        'postcode_compact' => 'M12PF',
        'address_line_1' => '10 Portland Street',
        'street' => 'Portland Street',
        'city' => 'Manchester',
        'is_active' => true,
    ]);
    UkAddress::create([
        'postcode' => 'e1 0bj',
        'postcode_compact' => 'E10BJ',
        'address_line_1' => '55 Commercial Road',
        'street' => 'Commercial Road',
        'city' => 'London',
        'is_active' => true,
    ]);
    UkAddress::create([
        'postcode' => 'SW1A 1AA',
        'postcode_compact' => 'SW1A1AA',
        'address_line_1' => '1 Buckingham Gate',
        'street' => 'Buckingham Gate',
        'city' => 'London',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.stores.uk-postcodes'));

    $response->assertStatus(200);
    $ids = collect($response->json('results'))->pluck('id')->all();

    expect($ids)->toContain('E1 0BJ');
    expect($ids)->toContain('M1 2PF');
    expect($ids)->not->toContain('SW1A 1AA');
    expect(array_count_values($ids)['M1 2PF'] ?? 0)->toBe(1);

    $sorted = $ids;
    usort($sorted, fn (string $a, string $b) => strnatcasecmp($a, $b));
    expect($ids)->toBe($sorted);

    $filtered = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.stores.uk-postcodes', ['q' => 'm12pf']));
    $filtered->assertStatus(200);
    expect(collect($filtered->json('results'))->pluck('id')->all())->toContain('M1 2PF');
});

test('admin can create store with valid uk postcode and normalized value is saved', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-stores');

    UkAddress::create([
        'postcode' => 'M1 2PF',
        'postcode_compact' => 'M12PF',
        'address_line_1' => '10 Portland Street',
        'street' => 'Portland Street',
        'city' => 'Manchester',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.store'), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Manchester Central Hub',
        'slug' => 'manchester-central-hub',
        'store_type' => 'both',
        'phone' => '01610001122',
        'full_address' => '10 Portland Street, Manchester',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => 'm1  2pf',
        'has_delivery' => '1',
        'delivery_fee' => '2.50',
        'min_order_amount' => '20.00',
    ]);

    $response->assertStatus(201);
    $store = Store::where('slug', 'manchester-central-hub')->first();
    expect($store)->not->toBeNull();
    expect($store->postcode)->toBe('M1 2PF');
});

test('admin cannot create store with arbitrary postcode not in uk_addresses', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-stores');

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.store'), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Invalid Postcode Store',
        'slug' => 'invalid-postcode-store',
        'store_type' => 'both',
        'phone' => '01610009999',
        'full_address' => '10 Fake Street',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => 'ZZ99 9ZZ',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['postcode'])
        ->assertJsonPath('errors.postcode.0', 'Please select a valid postcode from the UK address database.');
});

test('admin can update store with valid uk postcode and cannot set arbitrary postcode', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-stores');

    UkAddress::create([
        'postcode' => 'E1 0BJ',
        'postcode_compact' => 'E10BJ',
        'address_line_1' => '100 Commercial Road',
        'street' => 'Commercial Road',
        'city' => 'London',
        'is_active' => true,
    ]);

    $store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Update Postcode Store',
        'slug' => 'update-postcode-store',
        'store_type' => 'both',
        'phone' => '01711112222',
        'full_address' => '100 Commercial Road',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => '1205',
    ]);

    $invalidRes = $this->actingAs($admin, 'admin')->putJson(route('admin.stores.update', $store->id), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Update Postcode Store',
        'store_type' => 'both',
        'phone' => '01711112222',
        'full_address' => '100 Commercial Road',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => 'XX11 1XX',
    ]);

    $invalidRes->assertStatus(422)
        ->assertJsonValidationErrors(['postcode']);

    $validRes = $this->actingAs($admin, 'admin')->putJson(route('admin.stores.update', $store->id), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Update Postcode Store',
        'store_type' => 'both',
        'phone' => '01711112222',
        'full_address' => '100 Commercial Road',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => 'e1 0bj',
    ]);

    $validRes->assertStatus(200);
    $store->refresh();
    expect($store->postcode)->toBe('E1 0BJ');
});

test('admin can update unrelated fields on a store with an unchanged legacy postcode', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-stores');

    $store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Legacy Store',
        'slug' => 'legacy-store',
        'store_type' => 'both',
        'phone' => '01711113333',
        'full_address' => 'Dhanmondi 27',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => '1205',
    ]);

    $res = $this->actingAs($admin, 'admin')->putJson(route('admin.stores.update', $store->id), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Legacy Store Renamed',
        'store_type' => 'both',
        'phone' => '01711113333',
        'full_address' => 'Dhanmondi 27',
        'division_id' => $this->division->id,
        'district_id' => $this->district->id,
        'upazila_id' => $this->upazila->id,
        'local_area_id' => $this->localArea->id,
        'postcode' => '1205',
    ]);

    $res->assertStatus(200);
    $store->refresh();
    expect($store->name)->toBe('Legacy Store Renamed');
    expect($store->postcode)->toBe('1205');
});


test('admin can create international store in United States without bangladesh fields and validates US ZIP', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-stores');

    $invalid = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.store'), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'NYC Midtown Branch',
        'store_type' => 'both',
        'phone_country_code' => '+1',
        'phone' => '2125550199',
        'country_code' => 'US',
        'address_line_1' => '350 5th Ave',
        'city' => 'New York',
        'state_region' => 'New York',
        'postcode' => 'INVALID',
    ]);

    $invalid->assertStatus(422)->assertJsonValidationErrors(['postcode']);

    $valid = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.store'), [
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'NYC Midtown Branch',
        'store_type' => 'both',
        'phone_country_code' => '+1',
        'phone' => '2125550199',
        'country_code' => 'US',
        'address_line_1' => '350 5th Ave',
        'city' => 'New York',
        'state_region' => 'New York',
        'postcode' => '10118',
    ]);

    $valid->assertStatus(201);
    $store = Store::where('name', 'NYC Midtown Branch')->first();
    expect($store)->not->toBeNull();
    expect($store->country_code)->toBe('US');
    expect($store->country_name)->toBe('United States');
    expect($store->division_id)->toBeNull();
    expect($store->postcode)->toBe('10118');
    expect($store->store_code)->toStartWith('STR-');
});

test('admin can toggle store status and archive store safely', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['edit-stores', 'delete-stores']);

    $store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'London Soho Branch',
        'slug' => 'london-soho-branch',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '7123456789',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '12 Soho Square',
        'city' => 'London',
        'full_address' => '12 Soho Square, London, United Kingdom',
    ]);

    $toggleRes = $this->actingAs($admin, 'admin')->patchJson(route('admin.stores.toggle-status', $store->id));
    $toggleRes->assertStatus(200)->assertJsonPath('status', 'suspended');

    $deleteRes = $this->actingAs($admin, 'admin')->deleteJson(route('admin.stores.destroy', $store->id));
    $deleteRes->assertStatus(200);
    expect(Store::withTrashed()->find($store->id)?->deleted_at)->not->toBeNull();
});

test('admin can view, create, update, toggle status, and archive international vendor', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-vendors');

    $page = $this->actingAs($admin, 'admin')->get(route('admin.stores.vendors.index'));
    $page->assertStatus(200)->assertSee('Vendor Management');

    $createRes = $this->actingAs($admin, 'admin')->postJson(route('admin.stores.vendors.store'), [
        'name' => 'Thames Valley Cleaners Ltd',
        'owner_name' => 'Arthur Pendelton',
        'business_type' => 'Limited Company / LLC',
        'phone_country_code' => '+44',
        'phone' => '7700900123',
        'email' => 'arthur@thamescleaners.co.uk',
        'country_code' => 'GB',
        'address_line_1' => '22 Baker Street',
        'city' => 'London',
        'state_region' => 'Greater London',
        'postcode' => '',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => '12.50',
        'payment_terms' => 'Monthly Settlement',
        'payout_method' => 'Bank Transfer / Wire',
        'payout_details' => 'Sort Code 20-00-00, Acc 12345678',
    ]);

    $createRes->assertStatus(201);
    $vendor = Vendor::where('email', 'arthur@thamescleaners.co.uk')->first();
    expect($vendor)->not->toBeNull();
    expect($vendor->country_code)->toBe('GB');
    expect($vendor->country_name)->toBe('United Kingdom');
    expect($vendor->vendor_code)->toStartWith('VND-');

    $updateRes = $this->actingAs($admin, 'admin')->putJson(route('admin.stores.vendors.update', $vendor->id), [
        'name' => 'Thames Valley Cleaners UK Ltd',
        'owner_name' => 'Arthur Pendelton',
        'phone_country_code' => '+44',
        'phone' => '7700900123',
        'email' => 'arthur@thamescleaners.co.uk',
        'country_code' => 'CA',
        'address_line_1' => '100 King Street West',
        'city' => 'Toronto',
        'state_region' => 'Ontario',
        'postcode' => 'M5X 1A9',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => '15.00',
    ]);

    $updateRes->assertStatus(200);
    $vendor->refresh();
    expect($vendor->country_code)->toBe('CA');
    expect($vendor->postcode)->toBe('M5X 1A9');

    $toggleRes = $this->actingAs($admin, 'admin')->patchJson(route('admin.stores.vendors.toggle-status', $vendor->id));
    $toggleRes->assertStatus(200)->assertJsonPath('status', 'suspended');

    $archiveRes = $this->actingAs($admin, 'admin')->deleteJson(route('admin.stores.vendors.destroy', $vendor->id));
    $archiveRes->assertStatus(200);
    expect(Vendor::withTrashed()->find($vendor->id)?->deleted_at)->not->toBeNull();
});
