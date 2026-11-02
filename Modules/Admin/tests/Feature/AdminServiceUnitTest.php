<?php

use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-units', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-units', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'edit-units', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-units', 'guard_name' => 'admin']);
});

test('unauthenticated admin cannot view services units', function () {
    $response = $this->get(route('admin.services.units.index'));
    $response->assertRedirect(route('admin.login'));
});

test('admin with view-units permission can view units page', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-units');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.services.units.index'));
    $response->assertStatus(200);
    $response->assertSee('Service Pricing Units');
    $response->assertSee('Pricing Units for Laundry Services');
});

test('admin can fetch units json data for datatables', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('view-units');

    Unit::create([
        'name' => 'Per Kg Test',
        'short_name' => 'kg',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.services.units.data'));
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);
    $response->assertJsonFragment(['name' => 'Per Kg Test']);
});

test('admin with create-units permission can create a new laundry unit via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-units');

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.services.units.store'), [
        'name' => 'Per Blanket',
        'short_name' => 'blanket',
        'description' => 'Heavy wash for blankets',
        'is_active' => 1,
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('units', [
        'name' => 'Per Blanket',
        'short_name' => 'blanket',
    ]);
});

test('unit creation validates required and unique name', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('create-units');

    Unit::create(['name' => 'Per Piece Exclusive']);

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.services.units.store'), [
        'name' => 'Per Piece Exclusive',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
});

test('admin with edit-units permission can update unit', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-units');

    $unit = Unit::create(['name' => 'Per Kilo Old', 'short_name' => 'k']);

    $response = $this->actingAs($admin, 'admin')->putJson(route('admin.services.units.update', $unit), [
        'name' => 'Per Kg Updated',
        'short_name' => 'kg',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'name' => 'Per Kg Updated',
        'short_name' => 'kg',
    ]);
});

test('admin can toggle unit active status', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('edit-units');

    $unit = Unit::create(['name' => 'Per Basket', 'is_active' => true]);

    $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.services.units.toggle-status', $unit));
    $response->assertStatus(200);
    expect($unit->fresh()->is_active)->toBeFalse();
});

test('admin with delete-units permission can delete unit', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('delete-units');

    $unit = Unit::create(['name' => 'To Delete']);

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.services.units.destroy', $unit));
    $response->assertStatus(200);
    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
});
