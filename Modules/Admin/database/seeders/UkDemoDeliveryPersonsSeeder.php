<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;

class UkDemoDeliveryPersonsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $ukAddresses = UkAddress::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->unique('postcode_compact')
            ->values();

        if ($ukAddresses->isEmpty()) {
            return;
        }

        $stores = Store::query()->where('status', 'active')->orderBy('id')->get();
        $firstStore = $stores->first();
        $secondStore = $stores->skip(1)->first() ?? $firstStore;

        $m12pf = $ukAddresses->firstWhere('postcode_compact', 'M12PF');
        $otherPostcodes = $ukAddresses->filter(fn ($a) => $a->postcode_compact !== 'M12PF')->values();

        $pc1 = $m12pf ?? $ukAddresses->get(0);
        $pc2 = $otherPostcodes->get(0) ?? $pc1;
        $pc3 = $otherPostcodes->get(1) ?? $pc2;
        $pc4 = $otherPostcodes->get(2) ?? $pc3;

        $couriers = [
            [
                'code' => 'DP-UK-001',
                'full_name' => 'Oliver Bennett',
                'email' => 'oliver.bennett.demo@example.com',
                'phone_country_code' => '+44',
                'phone' => '7700900111',
                'date_of_birth' => '1991-04-12',
                'address_line_1' => $pc1->street_address ?: '14 Deansgate',
                'city' => $pc1->city ?: 'Manchester',
                'county_region' => $pc1->county ?: 'Greater Manchester',
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'residential_postcode' => $pc1->postcode,
                'full_residential_address' => trim(implode(', ', array_filter([$pc1->street_address, $pc1->city, $pc1->postcode, 'United Kingdom']))),
                'employment_type' => 'employee',
                'work_status' => 'active',
                'availability_status' => 'available',
                'joining_date' => now()->subMonths(6)->toDateString(),
                'store_id' => $firstStore?->id,
                'vehicle_type' => 'van',
                'vehicle_registration' => 'MN21 LND',
                'payment_calculation_type' => 'separate_rates',
                'per_collection_rate_minor' => 400,
                'per_delivery_rate_minor' => 450,
                'currency' => 'GBP',
                'coverage' => array_unique(array_filter([$pc1?->id => $pc1, $pc2?->id => $pc2], fn ($v) => $v !== null), SORT_REGULAR),
            ],
            [
                'code' => 'DP-UK-002',
                'full_name' => 'Amelia Clarke',
                'email' => 'amelia.clarke.demo@example.com',
                'phone_country_code' => '+44',
                'phone' => '7700900222',
                'date_of_birth' => '1994-09-23',
                'address_line_1' => $pc2->street_address ?: '28 Commercial Road',
                'city' => $pc2->city ?: 'London',
                'county_region' => $pc2->county ?: 'Greater London',
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'residential_postcode' => $pc2->postcode,
                'full_residential_address' => trim(implode(', ', array_filter([$pc2->street_address, $pc2->city, $pc2->postcode, 'United Kingdom']))),
                'employment_type' => 'employee',
                'work_status' => 'active',
                'availability_status' => 'available',
                'joining_date' => now()->subMonths(4)->toDateString(),
                'store_id' => $secondStore?->id,
                'vehicle_type' => 'motorbike',
                'vehicle_registration' => 'LD22 ABC',
                'payment_calculation_type' => 'separate_rates',
                'per_collection_rate_minor' => 350,
                'per_delivery_rate_minor' => 400,
                'currency' => 'GBP',
                'coverage' => array_filter([$pc2, $pc3], fn ($v) => $v !== null),
            ],
            [
                'code' => 'DP-UK-003',
                'full_name' => 'Liam Patel',
                'email' => 'liam.patel.demo@example.com',
                'phone_country_code' => '+44',
                'phone' => '7700900333',
                'date_of_birth' => '1989-11-05',
                'address_line_1' => $pc4->street_address ?: '9 Oxford Road',
                'city' => $pc4->city ?: 'London',
                'county_region' => $pc4->county ?: 'Greater London',
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'residential_postcode' => $pc4->postcode,
                'full_residential_address' => trim(implode(', ', array_filter([$pc4->street_address, $pc4->city, $pc4->postcode, 'United Kingdom']))),
                'employment_type' => 'contractor',
                'work_status' => 'active',
                'availability_status' => 'available',
                'joining_date' => now()->subMonths(2)->toDateString(),
                'store_id' => $secondStore?->id,
                'vehicle_type' => 'car',
                'vehicle_registration' => 'LN23 XYZ',
                'payment_calculation_type' => 'separate_rates',
                'per_collection_rate_minor' => 375,
                'per_delivery_rate_minor' => 425,
                'currency' => 'GBP',
                'coverage' => array_filter([$pc4], fn ($v) => $v !== null),
            ],
        ];

        foreach ($couriers as $data) {
            $coverageAddresses = $data['coverage'];
            unset($data['coverage']);

            $person = DeliveryPerson::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            $person->postcodes()->delete();
            $seen = [];
            foreach ($coverageAddresses as $addr) {
                $compact = UkAddress::compactPostcode((string) $addr->postcode);
                if ($compact === '' || isset($seen[$compact])) {
                    continue;
                }
                $seen[$compact] = true;

                DeliveryPersonPostcode::create([
                    'delivery_person_id' => $person->id,
                    'postcode' => UkAddress::normalizePostcode((string) $addr->postcode) ?? strtoupper((string) $addr->postcode),
                    'postcode_compact' => $compact,
                ]);
            }
        }
    }
}
