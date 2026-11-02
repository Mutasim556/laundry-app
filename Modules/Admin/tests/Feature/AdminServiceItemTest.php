<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-items', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-items', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'edit-items', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-items', 'guard_name' => 'admin']);
    Storage::fake('public');

    $this->category = ItemCategory::create([
        'name' => 'Shirts & Tops',
        'slug' => 'shirts-tops',
        'icon' => 'mdi-tshirt-crew',
        'is_active' => true,
    ]);
});

test('unauthenticated users cannot view items index', function () {
    $response = $this->get(route('admin.services.items.index'));
    $response->assertRedirect(route('admin.login'));
});

test('admin with view-items permission can view items index', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-items');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.services.items.index'));
    $response->assertStatus(200);
    $response->assertSee('Laundry Items');
});

test('admin can retrieve items data json for datatable', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-items');

    Item::create([
        'item_category_id' => $this->category->id,
        'name' => 'Cotton Shirt',
        'slug' => 'cotton-shirt',
        'icon' => 'mdi-tshirt-crew',
        'description' => '100% pure cotton shirt',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.items.data'));
    $response->assertStatus(200)
             ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category_id', 'category_name', 'icon', 'image_url', 'description', 'is_active', 'created_at']]]);
});

test('admin can check or generate unique slug for item via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-items');

    Item::create([
        'item_category_id' => $this->category->id,
        'name' => 'Suit Jacket',
        'slug' => 'suit-jacket',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.items.check-slug', [
        'name' => 'Suit Jacket',
    ]));

    $response->assertStatus(200)
             ->assertJson(['slug' => 'suit-jacket-1']);
});

test('admin can create item with category and image via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-items');

    $file = UploadedFile::fake()->image('polo_shirt.png');

    $response = $this->actingAs($admin, 'admin')->post(route('admin.services.items.store'), [
        'item_category_id' => $this->category->id,
        'name' => 'Polo T-Shirt',
        'slug' => 'polo-t-shirt',
        'icon' => 'mdi-tshirt-crew',
        'image' => $file,
        'description' => 'Casual polo t-shirt',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
             ->assertJson(['success' => true]);

    $item = Item::where('slug', 'polo-t-shirt')->first();
    expect($item)->not->toBeNull();
    expect($item->item_category_id)->toBe($this->category->id);
    expect($item->image)->not->toBeNull();
    Storage::disk('public')->assertExists($item->image);
});

test('admin can update item and category via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-items');

    $newCategory = ItemCategory::create([
        'name' => 'Bedding',
        'slug' => 'bedding',
        'icon' => 'mdi-hotel',
        'is_active' => true,
    ]);

    $item = Item::create([
        'item_category_id' => $this->category->id,
        'name' => 'Single Bedsheet',
        'slug' => 'single-bedsheet',
        'icon' => 'mdi-hotel',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('admin.services.items.update', $item), [
        'item_category_id' => $newCategory->id,
        'name' => 'Luxury Bedsheet Updated',
        'slug' => '',
        'icon' => 'mdi-hotel',
        'description' => 'Updated bedsheet description',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $item->refresh();
    expect($item->name)->toBe('Luxury Bedsheet Updated');
    expect($item->item_category_id)->toBe($newCategory->id);
    expect($item->slug)->toBe('luxury-bedsheet-updated');
});

test('admin can toggle item active status', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-items');

    $item = Item::create([
        'item_category_id' => $this->category->id,
        'name' => 'Toggle Test Item',
        'slug' => 'toggle-test-item',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.services.items.toggle-status', $item));

    $response->assertStatus(200)
             ->assertJson(['success' => true, 'is_active' => false]);

    expect($item->fresh()->is_active)->toBeFalse();
});

test('admin can delete item and its uploaded image', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('delete-items');

    $file = UploadedFile::fake()->image('delete_me.png');
    $path = $file->store('items', 'public');

    $item = Item::create([
        'item_category_id' => $this->category->id,
        'name' => 'Disposable Item',
        'slug' => 'disposable-item',
        'image' => $path,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.services.items.destroy', $item));

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('items', [
        'id' => $item->id,
    ]);
    Storage::disk('public')->assertMissing($path);
});
