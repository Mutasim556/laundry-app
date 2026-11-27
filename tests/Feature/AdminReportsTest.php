<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeReportAdmin(bool $super = true): Admin
{
    Permission::firstOrCreate(['name' => 'view-reports', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'export-reports', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Report Admin',
        'email' => 'rep-admin-' . Str::random(5) . '@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    if ($super) {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $admin->assignRole($role);
    }

    return $admin;
}

it('renders all 5 Admin Reports & Analytics pages and CSV export for authorized admins', function () {
    $admin = makeReportAdmin(true);
    $unpriv = makeReportAdmin(false);

    $this->actingAs($admin, 'admin')->get(route('admin.reports.index'))->assertOk()->assertSee('Overview &amp; Comparisons', false);
    $this->actingAs($admin, 'admin')->get(route('admin.reports.transactions'))->assertOk()->assertSee('Transactions &amp; Finance', false);
    $this->actingAs($admin, 'admin')->get(route('admin.reports.stores'))->assertOk()->assertSee('Store Performance');
    $this->actingAs($admin, 'admin')->get(route('admin.reports.delivery-persons'))->assertOk()->assertSee('Delivery Person Performance');
    $this->actingAs($admin, 'admin')->get(route('admin.reports.problems'))->assertOk()->assertSee('Problem &amp; Issue Tracker', false);

    foreach (['overview', 'website', 'customers', 'bookings', 'bottlenecks', 'tags', 'profitability', 'earnings', 'withdrawals', 'cash-custody', 'stores-financials', 'delivery-persons-earnings', 'delivery-comparison', 'activity'] as $type) {
        $this->actingAs($admin, 'admin')->get(route('admin.reports.show', $type))->assertOk();
    }

    $csv = $this->actingAs($admin, 'admin')->get(route('admin.reports.export'));
    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv');

    $this->actingAs($unpriv, 'admin')->get(route('admin.reports.index'))->assertForbidden();
});
