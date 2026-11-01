<?php

use Modules\Admin\Models\Admin;

test('admin login screen can be rendered', function () {
    $response = $this->get(route('admin.login'));

    $response->assertStatus(200);
    $response->assertSee('Admin Portal');
});

test('admin can authenticate using login screen', function () {
    $admin = Admin::factory()->create([
        'email' => 'test-admin@laundry.com',
        'password' => 'password123',
    ]);

    $response = $this->post(route('admin.login.store'), [
        'email' => 'test-admin@laundry.com',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($admin, 'admin');
    $response->assertRedirect(route('admin.dashboard'));
});

test('admin cannot authenticate with invalid password', function () {
    $admin = Admin::factory()->create([
        'email' => 'test-admin2@laundry.com',
        'password' => 'password123',
    ]);

    $response = $this->post(route('admin.login.store'), [
        'email' => 'test-admin2@laundry.com',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('admin');
    $response->assertSessionHasErrors('email');
});

test('admin cannot access dashboard when not authenticated', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('admin can log out', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->post(route('admin.logout'));

    $this->assertGuest('admin');
    $response->assertRedirect(route('admin.login'));
});

test('authenticated admin can render dashboard view', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Welcome back, ' . $admin->name);
    $response->assertSee('Total Orders');
});

test('authenticated admin visiting login page redirects to dashboard', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.login'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('blocked admin cannot authenticate and sees blocked message', function () {
    $admin = Admin::factory()->create([
        'email' => 'blocked-admin@laundry.com',
        'password' => 'password123',
        'is_blocked' => true,
    ]);

    $response = $this->post(route('admin.login.store'), [
        'email' => 'blocked-admin@laundry.com',
        'password' => 'password123',
    ]);

    $this->assertGuest('admin');
    $response->assertSessionHasErrors(['email' => 'Your administrator account has been blocked. Please contact system support.']);
});

test('blocked admin is logged out immediately by middleware on authenticated route', function () {
    $admin = Admin::factory()->create([
        'is_blocked' => true,
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $this->assertGuest('admin');
    $response->assertRedirect(route('admin.login'));
    $response->assertSessionHasErrors('email');
});
