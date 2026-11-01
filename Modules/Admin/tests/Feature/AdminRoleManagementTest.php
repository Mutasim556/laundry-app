<?php

use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('authenticated admin can view roles list page', function () {
    $admin = Admin::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.roles.index'));

    $response->assertStatus(200);
    $response->assertSee('Roles and Permissions Management');
    $response->assertSee('Add New Role');
});

test('admin can create a role for admin guard and web guard', function () {
    $admin = Admin::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    // Create admin guard role
    $response = $this->actingAs($admin, 'admin')->post(route('admin.roles.store'), [
        'name' => 'manager',
        'guard_name' => 'admin',
    ]);

    $response->assertRedirect(route('admin.roles.index'));
    expect(Role::where('name', 'manager')->where('guard_name', 'admin')->exists())->toBeTrue();

    // Create web guard role
    $responseWeb = $this->actingAs($admin, 'admin')->post(route('admin.roles.store'), [
        'name' => 'vip-customer',
        'guard_name' => 'web',
    ]);

    $responseWeb->assertRedirect(route('admin.roles.index'));
    expect(Role::where('name', 'vip-customer')->where('guard_name', 'web')->exists())->toBeTrue();
});

test('admin can update role and permissions', function () {
    $admin = Admin::factory()->create();
    $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($superRole);

    $role = Role::create(['name' => 'staff', 'guard_name' => 'admin']);
    $permission = Permission::firstOrCreate(['name' => 'view-orders', 'guard_name' => 'admin']);

    // Update role name
    $response = $this->actingAs($admin, 'admin')->put(route('admin.roles.update', $role), [
        'name' => 'senior-staff',
        'guard_name' => 'admin',
    ]);

    $response->assertRedirect(route('admin.roles.index'));
    expect($role->fresh()->name)->toBe('senior-staff');

    // Update permissions
    $permResponse = $this->actingAs($admin, 'admin')->put(route('admin.roles.permissions.update', $role), [
        'permissions' => ['view-orders'],
    ]);

    $permResponse->assertRedirect(route('admin.roles.index'));
    expect($role->fresh()->hasPermissionTo('view-orders'))->toBeTrue();
});

test('admin can delete a role but not super-admin', function () {
    $admin = Admin::factory()->create();
    $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $role = Role::create(['name' => 'temporary-role', 'guard_name' => 'admin']);

    // Delete normal role
    $response = $this->actingAs($admin, 'admin')->delete(route('admin.roles.destroy', $role));
    $response->assertRedirect(route('admin.roles.index'));
    expect(Role::where('id', $role->id)->exists())->toBeFalse();

    // Cannot delete super-admin
    $superDeleteResponse = $this->actingAs($admin, 'admin')->delete(route('admin.roles.destroy', $superAdminRole));
    $superDeleteResponse->assertRedirect(route('admin.roles.index'));
    expect(Role::where('id', $superAdminRole->id)->exists())->toBeTrue();
});

test('roles json data and users by role endpoint work via ajax', function () {
    $admin = Admin::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    // Test roles json endpoint
    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.roles.data'));
    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => [['id', 'name', 'guard_name', 'users_count', 'permissions_count']]]);

    // Test users by role endpoint
    $usersResponse = $this->actingAs($admin, 'admin')->getJson(route('admin.roles.users', $role));
    $usersResponse->assertStatus(200);
    $usersResponse->assertJsonStructure(['role', 'guard', 'count', 'users']);
    expect($usersResponse->json('count'))->toBeGreaterThanOrEqual(1);
});

test('role name must be unique within the same guard but can be shared across guards', function () {
    $admin = Admin::factory()->create();
    $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin->assignRole($superRole);

    // Create manager under admin guard
    $res1 = $this->actingAs($admin, 'admin')->postJson(route('admin.roles.store'), [
        'name' => 'manager',
        'guard_name' => 'admin',
    ]);
    $res1->assertStatus(200);

    // Duplicate manager under admin guard should fail validation
    $resDuplicate = $this->actingAs($admin, 'admin')->postJson(route('admin.roles.store'), [
        'name' => 'manager',
        'guard_name' => 'admin',
    ]);
    $resDuplicate->assertStatus(422);
    $resDuplicate->assertJsonValidationErrors('name');

    // Same manager name under web guard should succeed
    $resWeb = $this->actingAs($admin, 'admin')->postJson(route('admin.roles.store'), [
        'name' => 'manager',
        'guard_name' => 'web',
    ]);
    $resWeb->assertStatus(200);
});
