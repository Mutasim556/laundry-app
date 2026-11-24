<?php

use App\Services\AdminSidebarVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeSidebarSuperAdmin(): Admin
{
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $perm = Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    $role->givePermissionTo($perm);

    $admin = Admin::create([
        'name' => 'Sidebar Admin',
        'email' => 'sidebar-admin@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $admin->assignRole($role);

    return $admin;
}

beforeEach(function () {
    $this->visibleJsonPath = config_path('visible_admin.json');
    $this->originalVisibleJson = file_get_contents($this->visibleJsonPath);
});

afterEach(function () {
    if ($this->originalVisibleJson !== false) {
        file_put_contents($this->visibleJsonPath, $this->originalVisibleJson);
    }
});

it('shows all admin sidebar menus when config/visible_admin.json has all entries true', function () {
    $admin = makeSidebarSuperAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Roles &amp; Permissions', false)
        ->assertSee('Store Management')
        ->assertSee('Stores')
        ->assertSee('Vendors')
        ->assertSee('Delivery Persons')
        ->assertSee('Booking Management')
        ->assertSee('Refund &amp; Returns', false)
        ->assertSee('Current Account')
        ->assertSee('data-feather="grid"', false)
        ->assertSee('has-arrow')
        ->assertSee('collapse first-level');
});

it('hides top-level menus when visibility is false while keeping direct routes accessible', function () {
    $admin = makeSidebarSuperAdmin();

    $config = json_decode($this->originalVisibleJson, true);
    $config['Dashboard']['visibility'] = false;
    $config['Booking Management']['visibility'] = false;
    $config['Current Account']['visibility'] = false;
    file_put_contents($this->visibleJsonPath, json_encode($config, JSON_PRETTY_PRINT));

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertDontSee('<span class="hide-menu">Dashboard</span>', false)
        ->assertDontSee('<span class="hide-menu">Booking Management</span>', false)
        ->assertDontSee('<span class="hide-menu">Current Account</span>', false)
        ->assertSee('<span class="hide-menu">Store Management</span>', false);

    // Direct URLs continue working normally (no middleware/route/permission changes)
    $this->actingAs($admin, 'admin')->get(route('admin.bookings.index'))->assertOk();
    $this->actingAs($admin, 'admin')->get(route('admin.current-account.index'))->assertOk();
});

it('supports independent submenu visibility and hides parent when parent is false or all children are false', function () {
    $admin = makeSidebarSuperAdmin();

    // Case 1: Only Vendors child is false
    $config = json_decode($this->originalVisibleJson, true);
    $config['Store Management']['visibility'] = true;
    $config['Store Management']['children']['Vendors']['visibility'] = false;
    file_put_contents($this->visibleJsonPath, json_encode($config, JSON_PRETTY_PRINT));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.stores.index'))
        ->assertOk()
        ->assertSee('<span class="hide-menu">Store Management</span>', false)
        ->assertSee('<span class="hide-menu">Stores</span>', false)
        ->assertSee('<span class="hide-menu">Store Withdrawals</span>', false)
        ->assertDontSee('<span class="hide-menu">Vendors</span>', false);

    // Direct vendor URL remains accessible
    $this->actingAs($admin, 'admin')->get(route('admin.stores.vendors.index'))->assertOk();

    // Case 2: Parent Store Management is false -> hides parent and all children
    $config['Store Management']['visibility'] = false;
    $config['Store Management']['children']['Vendors']['visibility'] = true;
    file_put_contents($this->visibleJsonPath, json_encode($config, JSON_PRETTY_PRINT));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('<span class="hide-menu">Store Management</span>', false)
        ->assertDontSee('<span class="hide-menu">Stores</span>', false)
        ->assertDontSee('<span class="hide-menu">Vendors</span>', false)
        ->assertDontSee('<span class="hide-menu">Store Withdrawals</span>', false);

    // Case 3: Parent true, all children false -> empty dropdown parent is hidden
    $config['Store Management']['visibility'] = true;
    $config['Store Management']['children']['Stores']['visibility'] = false;
    $config['Store Management']['children']['Vendors']['visibility'] = false;
    $config['Store Management']['children']['Store Withdrawals']['visibility'] = false;
    file_put_contents($this->visibleJsonPath, json_encode($config, JSON_PRETTY_PRINT));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('<span class="hide-menu">Store Management</span>', false);
});

it('defaults missing menu keys to visible and safely falls back when visible_admin.json is missing or invalid', function () {
    $admin = makeSidebarSuperAdmin();

    // Missing menu key defaults to true
    $service = new AdminSidebarVisibilityService($this->visibleJsonPath);
    expect($service->isVisible('NonExistent Future Menu'))->toBeTrue()
        ->and($service->isVisible('Store Management', 'NonExistent Child'))->toBeTrue();

    // Invalid JSON does not crash sidebar and keeps all menus visible
    file_put_contents($this->visibleJsonPath, '{invalid json');
    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<span class="hide-menu">Dashboard</span>', false)
        ->assertSee('<span class="hide-menu">Store Management</span>', false);

    // Missing file does not crash sidebar and keeps all menus visible
    @unlink($this->visibleJsonPath);
    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<span class="hide-menu">Dashboard</span>', false)
        ->assertSee('<span class="hide-menu">Booking Management</span>', false);
});
