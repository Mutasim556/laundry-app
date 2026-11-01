<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class WebPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * Web Guard Permissions for frontend users.
         */
        $groups = [
            'Orders & Booking' => [
                'create-order',
                'view-order-history',
                'cancel-order',
                'track-order',
            ],
            'Profile & Addresses' => [
                'manage-addresses',
                'update-profile',
            ],
            'Wallet & Payments' => [
                'view-wallet',
                'add-funds',
                'view-transactions',
            ],
            'Reviews & Feedback' => [
                'submit-review',
            ],
        ];

        foreach ($groups as $groupName => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    [
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ],
                    [
                        'group_name' => $groupName,
                    ]
                );
            }
        }
    }
}
