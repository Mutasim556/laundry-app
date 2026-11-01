<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;

test('admin profile screen can be rendered', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.profile.edit'));

    $response->assertStatus(200);
    $response->assertSee('Admin Profile');
    $response->assertSee('Personal Details and Avatar');
    $response->assertSee($admin->email);
});

test('admin can update profile information and avatar', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@laundry.com',
    ]);

    $file = UploadedFile::fake()->image('new-avatar.jpg', 200, 200);

    $response = $this->actingAs($admin, 'admin')->patch(route('admin.profile.update'), [
        'name' => 'New Admin Name',
        'email' => 'new@laundry.com',
        'avatar' => $file,
    ]);

    $response->assertRedirect(route('admin.profile.edit'));
    $response->assertSessionHas('status', 'profile-updated');

    $admin->refresh();
    expect($admin->name)->toBe('New Admin Name');
    expect($admin->email)->toBe('new@laundry.com');
    expect($admin->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($admin->avatar);
});

test('admin can change password with valid current password', function () {
    $admin = Admin::factory()->create([
        'password' => 'old-password123',
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('admin.profile.password.update'), [
        'current_password' => 'old-password123',
        'password' => 'new-secret-password456',
        'password_confirmation' => 'new-secret-password456',
    ]);

    $response->assertRedirect(route('admin.profile.edit'));
    $response->assertSessionHas('status', 'password-updated');

    $admin->refresh();
    expect(Hash::check('new-secret-password456', $admin->password))->toBeTrue();
});

test('admin cannot change password with incorrect current password', function () {
    $admin = Admin::factory()->create([
        'password' => 'old-password123',
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('admin.profile.password.update'), [
        'current_password' => 'wrong-current-password',
        'password' => 'new-secret-password456',
        'password_confirmation' => 'new-secret-password456',
    ]);

    $response->assertSessionHasErrors('current_password');
});
