<?php

use App\Models\User;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole($superRole);
});

test('authenticated admin can view users list page', function () {
    $response = $this->actingAs($this->admin, 'admin')->get(route('admin.users.index'));

    $response->assertStatus(200);
    $response->assertSee('Application Users Management');
    $response->assertSee('Add New User');
    $response->assertSee('Filter by Role');
});

test('users json data endpoint returns list and filters by web guard role', function () {
    $roleVip = Role::firstOrCreate(['name' => 'vip-customer', 'guard_name' => 'web']);
    $roleRegular = Role::firstOrCreate(['name' => 'regular-customer', 'guard_name' => 'web']);

    $user1 = User::factory()->create(['name' => 'VIP User', 'email' => 'vip@example.com']);
    $user1->assignRole($roleVip);

    $user2 = User::factory()->create(['name' => 'Regular User', 'email' => 'regular@example.com']);
    $user2->assignRole($roleRegular);

    $user3 = User::factory()->create(['name' => 'No Role User', 'email' => 'norole@example.com']);

    // 1. All users
    $resAll = $this->actingAs($this->admin, 'admin')->getJson(route('admin.users.data'));
    $resAll->assertStatus(200);
    $allEmails = collect($resAll->json('data'))->pluck('email');
    expect($allEmails)->toContain('vip@example.com');
    expect($allEmails)->toContain('regular@example.com');
    expect($allEmails)->toContain('norole@example.com');

    // 2. Filter by vip-customer
    $resVip = $this->actingAs($this->admin, 'admin')->getJson(route('admin.users.data', ['role' => 'vip-customer']));
    $resVip->assertStatus(200);
    $vipEmails = collect($resVip->json('data'))->pluck('email');
    expect($vipEmails)->toContain('vip@example.com');
    expect($vipEmails)->not->toContain('regular@example.com');
    expect($vipEmails)->not->toContain('norole@example.com');

    // 3. Filter by 'none' (users without role)
    $resNone = $this->actingAs($this->admin, 'admin')->getJson(route('admin.users.data', ['role' => 'none']));
    $resNone->assertStatus(200);
    $noneEmails = collect($resNone->json('data'))->pluck('email');
    expect($noneEmails)->toContain('norole@example.com');
    expect($noneEmails)->not->toContain('vip@example.com');
    expect($noneEmails)->not->toContain('regular@example.com');
});

test('admin can create a new user with single web role via ajax', function () {
    $role = Role::firstOrCreate(['name' => 'premium-member', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin, 'admin')->postJson(route('admin.users.store'), [
        'name' => 'Alice Customer',
        'email' => 'alice.customer@example.com',
        'password' => 'secret1234',
        'roles' => ['premium-member'],
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $created = User::where('email', 'alice.customer@example.com')->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('Alice Customer');
    expect($created->hasRole('premium-member'))->toBeTrue();
});

test('admin can update user and their assigned role via ajax', function () {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $role = Role::firstOrCreate(['name' => 'vip-customer', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin, 'admin')->putJson(route('admin.users.update', $user), [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'roles' => ['vip-customer'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $freshUser = $user->fresh();
    expect($freshUser->name)->toBe('New Name');
    expect($freshUser->email)->toBe('new@example.com');
    expect($freshUser->hasRole('vip-customer'))->toBeTrue();
});

test('admin can assign single role specifically to user via roles endpoint', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'subscriber', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin, 'admin')->putJson(route('admin.users.roles.update', $user), [
        'roles' => ['subscriber'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect($user->fresh()->hasRole('subscriber'))->toBeTrue();
});

test('admin can delete a user via ajax', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin, 'admin')->deleteJson(route('admin.users.destroy', $user));
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    expect(User::where('id', $user->id)->exists())->toBeFalse();
});

test('admin can toggle block and unblock on users via ajax', function () {
    $user = User::factory()->create(['is_blocked' => false]);

    // Block user
    $resBlock = $this->actingAs($this->admin, 'admin')->patchJson(route('admin.users.toggle-block', $user));
    $resBlock->assertStatus(200);
    $resBlock->assertJson(['success' => true, 'is_blocked' => true]);
    expect($user->fresh()->is_blocked)->toBeTrue();

    // Unblock user
    $resUnblock = $this->actingAs($this->admin, 'admin')->patchJson(route('admin.users.toggle-block', $user));
    $resUnblock->assertStatus(200);
    $resUnblock->assertJson(['success' => true, 'is_blocked' => false]);
    expect($user->fresh()->is_blocked)->toBeFalse();
});
