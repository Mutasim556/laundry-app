<?php

namespace Modules\Admin\database\seeders;

use App\Support\InternationalAddressHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;
use Modules\Admin\Models\Vendor;

class UkDemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('UkDemoStoreSeeder can only be executed in the local environment.');
        }

        $ukLocations = UkAddress::query()
            ->where('is_active', true)
            ->whereNotNull('postcode_compact')
            ->where('postcode_compact', '!=', '')
            ->whereNotNull('postcode')
            ->whereRaw("TRIM(postcode) != ''")
            ->get()
            ->groupBy('postcode_compact')
            ->map(function ($rows) {
                $row = $rows->first();
                $postcode = UkAddress::normalizePostcode((string) $row->postcode)
                    ?? strtoupper(trim((string) $row->postcode));

                return [
                    'postcode' => $postcode,
                    'city' => trim((string) ($row->city ?: 'London')),
                    'county' => trim((string) ($row->county ?: 'Greater London')),
                    'street' => trim((string) ($row->street ?: $row->address_line_1 ?: 'High Street')),
                ];
            })
            ->sortBy('postcode')
            ->values();

        if ($ukLocations->isEmpty()) {
            throw new \RuntimeException('No active UK addresses found in uk_addresses. Seed or import uk_addresses first.');
        }

        $orderedLocations = $ukLocations->sortByDesc(fn ($loc) => stripos($loc['city'], 'Manchester') !== false ? 1 : 0)->values();
        $vendors = Vendor::where('vendor_code', 'like', 'DEMO-VND-%')->orderBy('vendor_code')->get()->keyBy('vendor_code');
        $adminId = Admin::orderBy('id')->value('id');
        $serviceIds = Service::where('is_active', true)->pluck('id')->take(5)->values()->all();

        $storesData = [
            ['code' => 'DEMO-STR-001', 'name' => 'Demo Piccadilly Laundry Hub', 'type' => 'both', 'ownership' => 'my_store', 'vendor_code' => null, 'status' => 'active', 'contact' => 'Liam Carter', 'phone' => '1614960201', 'email' => 'piccadilly.store@example.com', 'fee' => 2.50, 'min' => 18.00, 'cap' => 140],
            ['code' => 'DEMO-STR-002', 'name' => 'Demo Manchester Central Store', 'type' => 'both', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-001', 'status' => 'active', 'contact' => 'Emma Foster', 'phone' => '1614960202', 'email' => 'manchester.store@example.com', 'fee' => 2.00, 'min' => 20.00, 'cap' => 120],
            ['code' => 'DEMO-STR-003', 'name' => 'Demo Northern Laundry Point', 'type' => 'laundry', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-001', 'status' => 'active', 'contact' => 'Noah Phillips', 'phone' => '1614960203', 'email' => 'northern.store@example.com', 'fee' => 3.00, 'min' => 15.00, 'cap' => 95],
            ['code' => 'DEMO-STR-004', 'name' => 'Demo City Collection Centre', 'type' => 'both', 'ownership' => 'my_store', 'vendor_code' => null, 'status' => 'active', 'contact' => 'Olivia Shaw', 'phone' => '2079460204', 'email' => 'citycentre.store@example.com', 'fee' => 0.00, 'min' => 25.00, 'cap' => 160],
            ['code' => 'DEMO-STR-005', 'name' => 'Demo Express Garment Care', 'type' => 'dry_cleaning', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-002', 'status' => 'active', 'contact' => 'Jack Robinson', 'phone' => '2079460205', 'email' => 'express.store@example.com', 'fee' => 3.50, 'min' => 20.00, 'cap' => 110],
            ['code' => 'DEMO-STR-006', 'name' => 'Demo Premium Laundry Studio', 'type' => 'both', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-002', 'status' => 'active', 'contact' => 'Grace Walker', 'phone' => '2079460206', 'email' => 'studio.store@example.com', 'fee' => 2.50, 'min' => 20.00, 'cap' => 130],
            ['code' => 'DEMO-STR-007', 'name' => 'Demo Westminster Care Depot', 'type' => 'dry_cleaning', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-003', 'status' => 'active', 'contact' => 'Harry Turner', 'phone' => '2079460207', 'email' => 'westminster.store@example.com', 'fee' => 4.00, 'min' => 30.00, 'cap' => 85],
            ['code' => 'DEMO-STR-008', 'name' => 'Demo St Martin Press & Wash', 'type' => 'both', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-003', 'status' => 'active', 'contact' => 'Mia Cooper', 'phone' => '2079460208', 'email' => 'stmartin.store@example.com', 'fee' => 2.00, 'min' => 20.00, 'cap' => 100],
            ['code' => 'DEMO-STR-009', 'name' => 'Demo Portland Place Cleaners', 'type' => 'dry_cleaning', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-004', 'status' => 'active', 'contact' => 'Leo Morgan', 'phone' => '2079460209', 'email' => 'portland.store@example.com', 'fee' => 3.00, 'min' => 25.00, 'cap' => 90],
            ['code' => 'DEMO-STR-010', 'name' => 'Demo East London Wash House', 'type' => 'laundry', 'ownership' => 'my_store', 'vendor_code' => null, 'status' => 'active', 'contact' => 'Isla Ward', 'phone' => '2079460210', 'email' => 'eastlondon.store@example.com', 'fee' => 1.99, 'min' => 15.00, 'cap' => 150],
            ['code' => 'DEMO-STR-011', 'name' => 'Demo Regent Express Hub', 'type' => 'both', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-005', 'status' => 'suspended', 'contact' => 'Arthur Scott', 'phone' => '2079460211', 'email' => 'regent.store@example.com', 'fee' => 2.50, 'min' => 20.00, 'cap' => 70],
            ['code' => 'DEMO-STR-012', 'name' => 'Demo Heritage Archive Branch', 'type' => 'dry_cleaning', 'ownership' => 'vendor_store', 'vendor_code' => 'DEMO-VND-006', 'status' => 'suspended', 'contact' => 'Ruby Clarke', 'phone' => '2079460212', 'email' => 'heritage.store@example.com', 'fee' => 3.00, 'min' => 22.00, 'cap' => 60],
        ];

        DB::transaction(function () use ($storesData, $orderedLocations, $vendors, $adminId, $serviceIds) {
            foreach ($storesData as $index => $item) {
                $loc = $orderedLocations[$index % $orderedLocations->count()];
                $line1 = (20 + ($index * 3)) . ' ' . $loc['street'];
                $vendorId = $item['vendor_code'] ? ($vendors->get($item['vendor_code'])?->id) : null;
                $ownership = $vendorId ? 'vendor_store' : 'my_store';

                Store::withTrashed()->updateOrCreate(
                    ['store_code' => $item['code']],
                    [
                        'ownership_type' => $ownership,
                        'vendor_id' => $vendorId,
                        'admin_id' => $adminId,
                        'status' => $item['status'],
                        'name' => $item['name'],
                        'slug' => Str::slug($item['name']),
                        'description' => 'UK demo branch serving ' . $loc['city'] . ' (' . $loc['postcode'] . ').',
                        'contact_person' => $item['contact'],
                        'phone_country_code' => '+44',
                        'phone' => $item['phone'],
                        'email' => $item['email'],
                        'store_type' => $item['type'],
                        'country_code' => 'GB',
                        'country_name' => 'United Kingdom',
                        'address_line_1' => $line1,
                        'address_line_2' => 'Ground Floor',
                        'city' => $loc['city'],
                        'state_region' => $loc['county'],
                        'postcode' => $loc['postcode'],
                        'full_address' => InternationalAddressHelper::composeFullAddress(
                            $line1,
                            'Ground Floor',
                            $loc['city'],
                            $loc['county'],
                            $loc['postcode'],
                            'United Kingdom'
                        ),
                        'service_ids' => $serviceIds,
                        'pricing_model' => 'per_item',
                        'min_order_amount' => $item['min'],
                        'has_delivery' => true,
                        'delivery_fee' => $item['fee'],
                        'free_delivery_threshold' => 45.00,
                        'turnaround_time' => $item['index_turnaround'] ?? '24 Hours',
                        'capacity' => $item['cap'],
                        'is_accepting_orders' => $item['status'] === 'active',
                        'service_areas_text' => $loc['city'] . ' & surrounding ' . $loc['postcode'] . ' districts',
                        'deleted_at' => null,
                    ]
                );
            }
        });
    }
}
