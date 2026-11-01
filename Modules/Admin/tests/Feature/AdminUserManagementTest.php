<?php

use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
});

test('authenticated admin can view admins list page', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole($this->superRole);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.admins.index'));

    $response->assertStatus(200);
    $response->assertSee('Admin Users Management');
    $response->assertSee('Add New Admin');
});

test('admins json data endpoint returns list with assigned admin guard roles', function () {
    $admin = Admin::factory()->create(['name' => 'Alice Admin', 'email' => 'alice@laundry.com']);
    $admin->assignRole($this->superRole);
    $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    $response = $this->actingAs($admin, 'admin')->getJson(route('admin.admins.data'));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'name',
                'email',
                'avatar_url',
                'roles',
                'role_names',
                'created_at',
                'is_super_admin',
                'is_current_admin',
            ],
        ],
    ]);

    $data = collect($response->json('data'));
    $alice = $data->firstWhere('email', 'alice@laundry.com');
    expect($alice)->not->toBeNull();
    expect($alice['roles'])->toContain('manager');
});

test('admin can create a new admin user with admin guard roles via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole($this->superRole);
    $role = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.admins.store'), [
        'name' => 'Bob Builder',
        'email' => 'bob@laundry.com',
        'password' => 'password123',
        'roles' => ['accountant'],
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $created = Admin::where('email', 'bob@laundry.com')->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('Bob Builder');
    expect($created->hasRole('accountant'))->toBeTrue();
});

test('admin can update admin user details and roles via ajax', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole($this->superRole);
    $targetAdmin = Admin::factory()->create(['name' => 'Charlie', 'email' => 'charlie@laundry.com']);
    $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->putJson(route('admin.admins.update', $targetAdmin), [
        'name' => 'Charlie Updated',
        'email' => 'charlie.updated@laundry.com',
        'roles' => ['staff'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $freshTarget = $targetAdmin->fresh();
    expect($freshTarget->name)->toBe('Charlie Updated');
    expect($freshTarget->email)->toBe('charlie.updated@laundry.com');
    expect($freshTarget->hasRole('staff'))->toBeTrue();
});

test('admin can update roles specifically via dedicated endpoint', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole($this->superRole);
    $targetAdmin = Admin::factory()->create();
    $role = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->putJson(route('admin.admins.roles.update', $targetAdmin), [
        'roles' => ['editor'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect($targetAdmin->fresh()->hasRole('editor'))->toBeTrue();
});

test('admin cannot delete their own account or master super admin', function () {
    $admin = Admin::factory()->create(['email' => 'me@laundry.com']);
    $admin->assignRole($this->superRole);
    $masterSuper = Admin::firstOrCreate(
        ['email' => 'admin@laundry.com'],
        ['name' => 'Super Admin', 'password' => bcrypt('password')]
    );
    $masterSuper->assignRole($this->superRole);

    // Self-delete attempt
    $resSelf = $this->actingAs($admin, 'admin')->deleteJson(route('admin.admins.destroy', $admin));
    $resSelf->assertStatus(403);
    expect(Admin::where('id', $admin->id)->exists())->toBeTrue();

    // Master super-admin delete attempt
    $resMaster = $this->actingAs($admin, 'admin')->deleteJson(route('admin.admins.destroy', $masterSuper));
    $resMaster->assertStatus(403);
    expect(Admin::where('id', $masterSuper->id)->exists())->toBeTrue();

    // Deleting other normal admin succeeds
    $other = Admin::factory()->create();
    $resOther = $this->actingAs($admin, 'admin')->deleteJson(route('admin.admins.destroy', $other));
    $resOther->assertStatus(200);
    expect(Admin::where('id', $other->id)->exists())->toBeFalse();
});

test('admin can toggle block and unblock on other admins but not self or master super admin', function () {
    $admin = Admin::factory()->create(['email' => 'operator@laundry.com']);
    $admin->assignRole($this->superRole);
    $target = Admin::factory()->create(['is_blocked' => false]);
    $masterSuper = Admin::firstOrCreate(['email' => 'admin@laundry.com'], ['name' => 'Super', 'password' => bcrypt('password')]);
    $masterSuper->assignRole($this->superRole);

    // Block target admin
    $resBlock = $this->actingAs($admin, 'admin')->patchJson(route('admin.admins.toggle-block', $target));
    $resBlock->assertStatus(200);
    $resBlock->assertJson(['success' => true, 'is_blocked' => true]);
    expect($target->fresh()->is_blocked)->toBeTrue();

    // Unblock target admin
    $resUnblock = $this->actingAs($admin, 'admin')->patchJson(route('admin.admins.toggle-block', $target));
    $resUnblock->assertStatus(200);
    $resUnblock->assertJson(['success' => true, 'is_blocked' => false]);
    expect($target->fresh()->is_blocked)->toBeFalse();

    // Cannot block self
    $resSelf = $this->actingAs($admin, 'admin')->patchJson(route('admin.admins.toggle-block', $admin));
    $resSelf->assertStatus(403);

    // Cannot block master super-admin
    $resMaster = $this->actingAs($admin, 'admin')->patchJson(route('admin.admins.toggle-block', $masterSuper));
    $resMaster->assertStatus(403);
});
