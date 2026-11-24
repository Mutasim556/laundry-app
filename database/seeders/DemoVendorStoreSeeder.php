<?php

namespace Database\Seeders;

use App\Models\StoreUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\database\seeders\UkDemoStoreSeeder;
use Modules\Admin\database\seeders\UkDemoVendorSeeder;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Vendor;

class DemoVendorStoreSeeder extends Seeder
{
    /**
     * Remove existing vendors, stores, and store users, then seed demo vendors, stores, and store portal logins.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Remove existing store users
            StoreUser::query()->delete();

            // 2. Remove all existing stores (including soft-deleted)
            Store::withTrashed()->get()->each(function (Store $store) {
                $store->forceDelete();
            });

            // 3. Remove all existing vendors (including soft-deleted)
            Vendor::withTrashed()->get()->each(function (Vendor $vendor) {
                $vendor->forceDelete();
            });
        });

        // 4. Seed fresh UK demo vendors & stores
        $this->call([
            UkDemoVendorSeeder::class,
            UkDemoStoreSeeder::class,
        ]);

        // 5. Create Store Portal login credentials for active demo stores
        $adminId = Admin::orderBy('id')->value('id');
        $demoStoreAccounts = [
            [
                'store_code' => 'DEMO-STR-001',
                'name' => 'Liam Carter (Piccadilly Hub)',
                'email' => 'piccadilly.store@example.com',
                'phone_country_code' => '+44',
                'phone' => '1614960201',
                'role' => 'owner',
            ],
            [
                'store_code' => 'DEMO-STR-002',
                'name' => 'Emma Foster (Manchester Central)',
                'email' => 'manchester.store@example.com',
                'phone_country_code' => '+44',
                'phone' => '1614960202',
                'role' => 'owner',
            ],
            [
                'store_code' => 'DEMO-STR-003',
                'name' => 'Noah Phillips (Northern Point)',
                'email' => 'northern.store@example.com',
                'phone_country_code' => '+44',
                'phone' => '1614960203',
                'role' => 'owner',
            ],
            [
                'store_code' => 'DEMO-STR-004',
                'name' => 'Olivia Shaw (City Collection Centre)',
                'email' => 'citycentre.store@example.com',
                'phone_country_code' => '+44',
                'phone' => '2079460204',
                'role' => 'owner',
            ],
            [
                'store_code' => 'DEMO-STR-005',
                'name' => 'Jack Robinson (Express Garment Care)',
                'email' => 'express.store@example.com',
                'phone_country_code' => '+44',
                'phone' => '2079460205',
                'role' => 'owner',
            ],
        ];

        foreach ($demoStoreAccounts as $acct) {
            $store = Store::where('store_code', $acct['store_code'])->first();
            if (! $store) {
                continue;
            }

            StoreUser::updateOrCreate(
                ['email' => $acct['email']],
                [
                    'store_id' => $store->id,
                    'name' => $acct['name'],
                    'phone_country_code' => $acct['phone_country_code'],
                    'phone' => $acct['phone'],
                    'phone_normalized' => StoreUser::normalizePhone($acct['phone'], $acct['phone_country_code']),
                    'password' => 'Store@12345',
                    'role' => $acct['role'],
                    'is_active' => true,
                    'created_by_admin_id' => $adminId,
                ]
            );
        }
    }
}
