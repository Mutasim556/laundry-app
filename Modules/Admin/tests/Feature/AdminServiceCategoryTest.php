<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\ItemCategory;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-item-categories', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-item-categories', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'edit-item-categories', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-item-categories', 'guard_name' => 'admin']);
    Storage::fake('public');
});

test('unauthenticated users cannot view item categories', function () {
    $response = $this->get(route('admin.services.categories.index'));
    $response->assertRedirect(route('admin.login'));
});

test('admin with view-item-categories permission can view item categories index', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-item-categories');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.services.categories.index'));
    $response->assertStatus(200);
    $response->assertSee('Item Categories');
});

test('admin can retrieve categories data json for datatable', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-item-categories');

    ItemCategory::create([
        'name' => 'Test Shirts',
        'slug' => 'test-shirts',
        'icon' => 'mdi-tshirt-crew',
        'description' => 'Test shirts description',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.categories.data'));
    $response->assertStatus(200)
             ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'icon', 'image_url', 'description', 'is_active', 'created_at']]]);
});

test('admin can check and generate unique slug via ajax endpoint', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-item-categories');

    ItemCategory::create([
        'name' => 'Shirts & Tops',
        'slug' => 'shirts-tops',
        'icon' => 'mdi-tshirt-crew',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.categories.check-slug', [
        'name' => 'Shirts & Tops',
    ]));

    $response->assertStatus(200)
             ->assertJson(['slug' => 'shirts-tops-1']);
});

test('admin can create item category with uploaded image via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-item-categories');

    $file = UploadedFile::fake()->image('category_shirts.png', 400, 400);

    $response = $this->actingAs($admin, 'admin')->post(route('admin.services.categories.store'), [
        'name' => 'Suits & Formal Test With Image',
        'slug' => 'suits-formal-image',
        'icon' => 'mdi-tie',
        'image' => $file,
        'description' => 'High-end formal suits and tuxedos with image',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
             ->assertJson(['success' => true]);

    $created = ItemCategory::where('slug', 'suits-formal-image')->first();
    expect($created)->not->toBeNull();
    expect($created->image)->not->toBeNull();
    Storage::disk('public')->assertExists($created->image);
});

test('admin can update item category and replace image via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-item-categories');

    $oldFile = UploadedFile::fake()->image('old_bedding.jpg');
    $oldPath = $oldFile->store('categories', 'public');

    $category = ItemCategory::create([
        'name' => 'Bedding Original',
        'slug' => 'bedding-original',
        'icon' => 'mdi-hotel',
        'image' => $oldPath,
        'description' => 'Original description',
        'is_active' => true,
    ]);

    $newFile = UploadedFile::fake()->image('new_bedding.png');

    $response = $this->actingAs($admin, 'admin')->put(route('admin.services.categories.update', $category), [
        'name' => 'Bedding & Linens Updated',
        'slug' => '',
        'icon' => 'mdi-hotel',
        'image' => $newFile,
        'description' => 'Updated description',
        'is_active' => '1',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $category->refresh();
    expect($category->name)->toBe('Bedding & Linens Updated');
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($category->image);
});

test('admin can toggle item category active status', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-item-categories');

    $category = ItemCategory::create([
        'name' => 'Outerwear Toggle',
        'slug' => 'outerwear-toggle',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.services.categories.toggle-status', $category));

    $response->assertStatus(200)
             ->assertJson(['success' => true, 'is_active' => false]);

    expect($category->fresh()->is_active)->toBeFalse();
});

test('admin can delete item category and its image', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('delete-item-categories');

    $file = UploadedFile::fake()->image('to_delete.png');
    $path = $file->store('categories', 'public');

    $category = ItemCategory::create([
        'name' => 'Temporary Category',
        'slug' => 'temporary-category',
        'image' => $path,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.services.categories.destroy', $category));

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('item_categories', [
        'id' => $category->id,
    ]);
    Storage::disk('public')->assertMissing($path);
});
