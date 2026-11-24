<?php

namespace Modules\Admin\database\seeders;

use App\Support\InternationalAddressHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\UkAddress;
use Modules\Admin\Models\Vendor;

class UkDemoVendorSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('UkDemoVendorSeeder can only be executed in the local environment.');
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

        // Prefer Manchester first if available, then remaining distinct UK postcodes
        $orderedLocations = $ukLocations->sortByDesc(fn ($loc) => stripos($loc['city'], 'Manchester') !== false ? 1 : 0)->values();
        $serviceIds = Service::where('is_active', true)->pluck('id')->take(4)->values()->all();

        $vendorsData = [
            [
                'vendor_code' => 'DEMO-VND-001',
                'name' => 'Demo Manchester Laundry Partners',
                'owner_name' => 'Oliver Bennett',
                'business_type' => 'Limited Company / LLC',
                'phone' => '1614960101',
                'email' => 'vendors.manchester@example.com',
                'status' => 'active',
                'commission_type' => 'percentage',
                'commission_rate' => 12.50,
                'payment_terms' => 'Weekly Settlement',
                'payout_method' => 'Bank Transfer / Wire',
                'payout_details' => 'Sort Code 20-00-00, Acc 10002001',
                'description' => 'Demo UK partner specialising in high-volume wash, fold, and commercial linen care.',
            ],
            [
                'vendor_code' => 'DEMO-VND-002',
                'name' => 'Demo Northern Textile Services',
                'owner_name' => 'Charlotte Hughes',
                'business_type' => 'Limited Company / LLC',
                'phone' => '2079460102',
                'email' => 'vendors.northern@example.com',
                'status' => 'active',
                'commission_type' => 'percentage',
                'commission_rate' => 14.00,
                'payment_terms' => 'Bi-Weekly Settlement',
                'payout_method' => 'Stripe Connect',
                'payout_details' => 'Stripe Express UK Settlement',
                'description' => 'Demo UK textile & dry-cleaning partner serving central metropolitan postcodes.',
            ],
            [
                'vendor_code' => 'DEMO-VND-003',
                'name' => 'Demo City Fabric Care',
                'owner_name' => 'Amelia Wright',
                'business_type' => 'Partnership',
                'phone' => '2079460103',
                'email' => 'vendors.cityfabric@example.com',
                'status' => 'active',
                'commission_type' => 'percentage',
                'commission_rate' => 11.00,
                'payment_terms' => 'Monthly Settlement',
                'payout_method' => 'Bank Transfer / Wire',
                'payout_details' => 'Sort Code 40-10-20, Acc 30004002',
                'description' => 'Demo UK specialist in delicate fabrics, formal wear, and express pressing.',
            ],
            [
                'vendor_code' => 'DEMO-VND-004',
                'name' => 'Demo Premium Garment Specialists',
                'owner_name' => 'Henry Davies',
                'business_type' => 'Limited Company / LLC',
                'phone' => '2079460104',
                'email' => 'vendors.garment@example.com',
                'status' => 'active',
                'commission_type' => 'fixed',
                'commission_rate' => 3.50,
                'payment_terms' => 'Net 15',
                'payout_method' => 'Wise',
                'payout_details' => 'vendors.garment@example.com (GBP)',
                'description' => 'Demo UK luxury garment restoration and eco-solvent dry cleaning partner.',
            ],
            [
                'vendor_code' => 'DEMO-VND-005',
                'name' => 'Demo Express Cleaning Partners',
                'owner_name' => 'Sophie Evans',
                'business_type' => 'Corporation',
                'phone' => '2079460105',
                'email' => 'vendors.express@example.com',
                'status' => 'active',
                'commission_type' => 'percentage',
                'commission_rate' => 15.00,
                'payment_terms' => 'Weekly Settlement',
                'payout_method' => 'Bank Transfer / Wire',
                'payout_details' => 'Sort Code 60-11-33, Acc 50006003',
                'description' => 'Demo UK same-day and next-day turnaround laundry partner.',
            ],
            [
                'vendor_code' => 'DEMO-VND-006',
                'name' => 'Demo Heritage Dry Cleaning Group',
                'owner_name' => 'George Taylor',
                'business_type' => 'Sole Proprietorship',
                'phone' => '2079460106',
                'email' => 'vendors.heritage@example.com',
                'status' => 'suspended',
                'commission_type' => 'percentage',
                'commission_rate' => 10.00,
                'payment_terms' => 'Net 30',
                'payout_method' => 'Bank Transfer / Wire',
                'payout_details' => 'Sort Code 08-92-99, Acc 70008004',
                'description' => 'Demo inactive UK partner retained for status filter and archive testing.',
            ],
        ];

        DB::transaction(function () use ($vendorsData, $orderedLocations, $serviceIds) {
            foreach ($vendorsData as $index => $item) {
                $loc = $orderedLocations[$index % $orderedLocations->count()];
                $line1 = (10 + ($index * 4)) . ' ' . $loc['street'];

                Vendor::withTrashed()->updateOrCreate(
                    ['vendor_code' => $item['vendor_code']],
                    [
                        'name' => $item['name'],
                        'owner_name' => $item['owner_name'],
                        'business_type' => $item['business_type'],
                        'phone_country_code' => '+44',
                        'phone' => $item['phone'],
                        'email' => $item['email'],
                        'country_code' => 'GB',
                        'country_name' => 'United Kingdom',
                        'address_line_1' => $line1,
                        'address_line_2' => 'Unit ' . ($index + 1),
                        'city' => $loc['city'],
                        'state_region' => $loc['county'],
                        'postcode' => $loc['postcode'],
                        'business_address' => InternationalAddressHelper::composeFullAddress(
                            $line1,
                            'Unit ' . ($index + 1),
                            $loc['city'],
                            $loc['county'],
                            $loc['postcode'],
                            'United Kingdom'
                        ),
                        'description' => $item['description'],
                        'status' => $item['status'],
                        'commission_type' => $item['commission_type'],
                        'commission_rate' => $item['commission_rate'],
                        'payment_terms' => $item['payment_terms'],
                        'service_ids' => $serviceIds,
                        'payout_method' => $item['payout_method'],
                        'payout_details' => $item['payout_details'],
                        'deleted_at' => null,
                    ]
                );
            }
        });
    }
}
