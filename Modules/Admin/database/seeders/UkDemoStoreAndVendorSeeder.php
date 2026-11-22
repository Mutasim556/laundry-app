<?php

namespace Modules\Admin\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Vendor;

class UkDemoStoreAndVendorSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('UkDemoStoreAndVendorSeeder can only run in the local environment.');
        }

        $removedStores = 0;
        $archivedStores = 0;
        $removedVendors = 0;
        $archivedVendors = 0;

        DB::transaction(function () use (&$removedStores, &$archivedStores, &$removedVendors, &$archivedVendors) {
            // 1. Safely clear disposable non-DEMO stores (preserving any referenced by delivery persons/assignments)
            $legacyStores = Store::withTrashed()
                ->where(function ($q) {
                    $q->whereNull('store_code')->orWhere('store_code', 'not like', 'DEMO-STR-%');
                })
                ->get();

            foreach ($legacyStores as $store) {
                $hasRefs = DeliveryPerson::withTrashed()->where('store_id', $store->id)->exists()
                    || DeliveryAssignment::where('store_id', $store->id)->exists();

                if ($hasRefs) {
                    $store->update(['status' => 'suspended', 'is_accepting_orders' => false]);
                    if (! $store->trashed()) {
                        $store->delete();
                    }
                    $archivedStores++;
                } else {
                    $store->forceDelete();
                    $removedStores++;
                }
            }

            // 2. Safely clear disposable non-DEMO vendors (preserving any still linked to archived stores)
            $legacyVendors = Vendor::withTrashed()
                ->where(function ($q) {
                    $q->whereNull('vendor_code')->orWhere('vendor_code', 'not like', 'DEMO-VND-%');
                })
                ->get();

            foreach ($legacyVendors as $vendor) {
                $hasRefs = Store::withTrashed()->where('vendor_id', $vendor->id)->exists();

                if ($hasRefs) {
                    $vendor->update(['status' => 'suspended']);
                    if (! $vendor->trashed()) {
                        $vendor->delete();
                    }
                    $archivedVendors++;
                } else {
                    $vendor->forceDelete();
                    $removedVendors++;
                }
            }
        });

        $this->command?->info("Legacy cleanup: removed {$removedStores} store(s), archived {$archivedStores} store(s); removed {$removedVendors} vendor(s), archived {$archivedVendors} vendor(s).");

        $this->call([
            UkDemoVendorSeeder::class,
            UkDemoStoreSeeder::class,
        ]);
    }
}
