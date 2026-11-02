<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * Permissions grouped by feature / module.
         */
        $groups = [
            'Admins Management' => [
                'view-admins',
                'create-admins',
                'edit-admins',
                'delete-admins',
            ],
            'Users Management' => [
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
            ],
            'Roles & Permissions' => [
                'view-roles',
                'create-roles',
                'edit-roles',
                'delete-roles',
            ],
            'Orders' => [
                'view-orders',
                'create-orders',
                'edit-orders',
                'delete-orders',
                'update-order-status',
            ],
            'Units Management' => [
                'view-units',
                'create-units',
                'edit-units',
                'delete-units',
            ],
            'Item Categories' => [
                'view-item-categories',
                'create-item-categories',
                'edit-item-categories',
                'delete-item-categories',
            ],
            'Items Management' => [
                'view-items',
                'create-items',
                'edit-items',
                'delete-items',
            ],
            'Services & Pricing' => [
                'view-services',
                'create-services',
                'edit-services',
                'delete-services',
            ],
            'Stores Management' => [
                'view-stores',
                'create-stores',
                'edit-stores',
                'delete-stores',
                'manage-vendors',
            ],
            'Customers' => [
                'view-customers',
                'edit-customers',
                'delete-customers',
            ],
            'Reports & Settings' => [
                'view-reports',
                'export-reports',
                'manage-settings',
            ],
        ];

        foreach ($groups as $groupName => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    [
                        'name' => $permissionName,
                        'guard_name' => 'admin',
                    ],
                    [
                        'group_name' => $groupName,
                    ]
                );
            }
        }
    }
}
