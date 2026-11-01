<?php
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Role;

test('super-admin role is seeded and assigned to admin@laundry.com', function () {
    $this->artisan('db:seed', ['--class' => 'Modules\Admin\Database\Seeders\AdminDatabaseSeeder'])->assertSuccessful();

    $role = Role::where('name', 'super-admin')->where('guard_name', 'admin')->first();
    expect($role)->not->toBeNull();

    $admin = Admin::where('email', 'admin@laundry.com')->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('super-admin', 'admin'))->toBeTrue();
});

test('super-admin bypasses all gate checks implicitly', function () {
    $this->artisan('db:seed', ['--class' => 'Modules\Admin\Database\Seeders\AdminDatabaseSeeder'])->assertSuccessful();

    $admin = Admin::where('email', 'admin@laundry.com')->first();

    // Any arbitrary permission check passes without needing explicitly defined permissions
    expect(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('delete-any-order'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('manage-system-settings'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('anything-whatsoever'))->toBeTrue();
});
