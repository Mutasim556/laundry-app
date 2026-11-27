<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeDashboardAdmin(): Admin
{
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Dashboard Admin',
        'email' => 'dash-admin-' . Str::random(5) . '@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $admin->assignRole($role);

    return $admin;
}

it('renders the redesigned Fresh Go Admin Dashboard and all AJAX endpoints for authenticated admins', function () {
    $admin = makeDashboardAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Fresh Go Dashboard')
        ->assertSee('Current Action Required')
        ->assertSee('Booking Workflow Stages')
        ->assertSee('Booking Activity');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.dashboard.overview', ['preset' => 'this_month', 'group_by' => 'daily']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['period' => ['month', 'month_label'], 'data' => ['kpis', 'workflow_stages', 'trend']]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.dashboard.operations'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['operational_kpis', 'delivery_jobs', 'store_workload', 'action_required']]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.dashboard.finance', ['currency' => 'GBP']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.currency', 'GBP')
        ->assertJsonStructure(['data' => ['kpis', 'by_method', 'withdrawals']]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.dashboard.tags'))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.dashboard.activity'))
        ->assertOk()
        ->assertJsonPath('success', true);
});
