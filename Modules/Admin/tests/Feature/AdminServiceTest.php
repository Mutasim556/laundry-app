<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-services', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-services', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'edit-services', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-services', 'guard_name' => 'admin']);
    Storage::fake('public');

    $this->unit = Unit::create([
        'name' => 'Per Kilogram',
        'short_name' => 'kg',
        'is_active' => true,
    ]);
});

test('unauthenticated users cannot view services index', function () {
    $response = $this->get(route('admin.services.services.index'));
    $response->assertRedirect(route('admin.login'));
});

test('admin with view-services permission can view services index', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-services');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.services.services.index'));
    $response->assertStatus(200);
    $response->assertSee('Laundry Services');
});

test('admin can retrieve services data json for datatable', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-services');

    Service::create([
        'unit_id' => $this->unit->id,
        'name' => 'Wash & Fold Premium',
        'slug' => 'wash-fold-premium',
        'price' => 65.50,
        'icon' => 'mdi-washing-machine',
        'description' => 'Premium wash and fold service',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.services.data'));
    $response->assertStatus(200)
             ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'price', 'unit_id', 'unit_name', 'unit_short_name', 'icon', 'image_url', 'description', 'is_active', 'created_at']]]);
});

test('admin can check or generate unique slug for service via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-services');

    Service::create([
        'unit_id' => $this->unit->id,
        'name' => 'Dry Cleaning',
        'slug' => 'dry-cleaning',
        'price' => 150.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.services.check-slug', [
        'name' => 'Dry Cleaning',
    ]));

    $response->assertStatus(200)
             ->assertJson(['slug' => 'dry-cleaning-1']);
});

test('admin can create service with unit and image via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-services');

    $file = UploadedFile::fake()->image('dry_clean.png');

    $response = $this->actingAs($admin, 'admin')->post(route('admin.services.services.store'), [
        'unit_id' => $this->unit->id,
        'name' => 'Eco Dry Clean',
        'slug' => 'eco-dry-clean',
        'price' => '120.00',
        'icon' => 'mdi-hanger',
        'image' => $file,
        'description' => 'Eco-friendly chemical-free dry cleaning',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
             ->assertJson(['success' => true]);

    $service = Service::where('slug', 'eco-dry-clean')->first();
    expect($service)->not->toBeNull();
    expect($service->unit_id)->toBe($this->unit->id);
    expect($service->image)->not->toBeNull();
    Storage::disk('public')->assertExists($service->image);
});

test('admin can update service and pricing unit via ajax', function () {
    $newUnit = Unit::create([
        'name' => 'Per Piece',
        'short_name' => 'pcs',
        'is_active' => true,
    ]);

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-services');

    $service = Service::create([
        'unit_id' => $this->unit->id,
        'name' => 'Steam Press',
        'slug' => 'steam-press',
        'price' => 25.00,
        'icon' => 'mdi-spray',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('admin.services.services.update', $service), [
        'unit_id' => $newUnit->id,
        'name' => 'Steam Press Updated',
        'slug' => '',
        'price' => '35.50',
        'icon' => 'mdi-spray',
        'description' => 'Updated steam pressing description',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $service->refresh();
    expect($service->name)->toBe('Steam Press Updated');
    expect((float) $service->price)->toBe(35.50);
    expect($service->unit_id)->toBe($newUnit->id);
});

test('admin can upload multiple gallery images for service and retrieve via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-services');

    $category = ItemCategory::create([
        'name' => 'Tops',
        'slug' => 'tops',
        'is_active' => true,
    ]);

    $item = Item::create([
        'item_category_id' => $category->id,
        'name' => 'Formal Shirt',
        'slug' => 'formal-shirt',
        'is_active' => true,
    ]);

    $file1 = UploadedFile::fake()->image('shirt1.png');
    $file2 = UploadedFile::fake()->image('shirt2.png');
    $file3 = UploadedFile::fake()->image('shirt3.png');

    $response = $this->actingAs($admin, 'admin')->post(route('admin.services.services.store'), [
        'unit_id' => $this->unit->id,
        'item_category_id' => $category->id,
        'item_id' => $item->id,
        'name' => 'Executive Shirt Clean',
        'slug' => 'executive-shirt-clean',
        'price' => '45.00',
        'icon' => 'mdi-tshirt-crew',
        'images' => [$file1, $file2, $file3],
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
             ->assertJson(['success' => true]);

    $service = Service::where('slug', 'executive-shirt-clean')->first();
    expect($service)->not->toBeNull();
    expect($service->item_category_id)->toBe($category->id);
    expect($service->item_id)->toBe($item->id);
    expect($service->images)->toBeArray();
    expect(count($service->images))->toBe(3);
    expect(count($service->all_image_urls))->toBe(3);

    foreach ($service->images as $img) {
        Storage::disk('public')->assertExists($img);
    }
});

test('admin can fetch items dynamically by category id', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-services');

    $cat1 = ItemCategory::create(['name' => 'Category 1', 'slug' => 'cat-1', 'is_active' => true]);
    $cat2 = ItemCategory::create(['name' => 'Category 2', 'slug' => 'cat-2', 'is_active' => true]);

    $item1 = Item::create(['item_category_id' => $cat1->id, 'name' => 'Item 1A', 'slug' => 'item-1a', 'is_active' => true]);
    $item2 = Item::create(['item_category_id' => $cat2->id, 'name' => 'Item 2A', 'slug' => 'item-2a', 'is_active' => true]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.services.items-by-category', ['category_id' => $cat1->id]));
    $response->assertStatus(200)
             ->assertJsonCount(1)
             ->assertJsonFragment(['id' => $item1->id, 'name' => 'Item 1A']);
});

test('admin can toggle service active status', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-services');

    $service = Service::create([
        'unit_id' => $this->unit->id,
        'name' => 'Express Wash',
        'slug' => 'express-wash',
        'price' => 99.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.services.services.toggle-status', $service));

    $response->assertStatus(200)
             ->assertJson(['success' => true, 'is_active' => false]);

    expect($service->fresh()->is_active)->toBeFalse();
});

test('admin can delete service and its uploaded banner and gallery images', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('delete-services');

    $fileCover = UploadedFile::fake()->image('service_banner.png');
    $pathCover = $fileCover->store('services', 'public');

    $fileGallery = UploadedFile::fake()->image('service_gallery.png');
    $pathGallery = $fileGallery->store('services', 'public');

    $service = Service::create([
        'unit_id' => $this->unit->id,
        'name' => 'One-time Service',
        'slug' => 'one-time-service',
        'price' => 50.00,
        'image' => $pathCover,
        'images' => [$pathGallery],
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.services.services.destroy', $service));

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
    Storage::disk('public')->assertMissing($pathCover);
    Storage::disk('public')->assertMissing($pathGallery);
});
