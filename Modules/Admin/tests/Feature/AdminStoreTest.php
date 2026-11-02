<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Store;
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
    $response->assertSee('Laundry Stores');
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
