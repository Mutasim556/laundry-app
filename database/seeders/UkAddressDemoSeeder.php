<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\UkAddress;

class UkAddressDemoSeeder extends Seeder
{
    /**
     * Seed clearly-labeled DEMO UK address records for development and testing.
     *
     * Note: These records are marked `is_demo = true` and `source = 'demo_seeder'`
     * so they are distinguishable from verified production or imported addresses.
     */
    public function run(): void
    {
        $demoAddresses = [
            // E1 0BJ (Stepney / Whitechapel demo cluster)
            [
                'postcode' => 'E1 0BJ',
                'address_line_1' => 'Flat 1, Brockmer House',
                'address_line_2' => 'Crowder Street',
                'building_name' => 'Brockmer House',
                'building_number' => 'Flat 1',
                'street' => 'Crowder Street',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5112000,
                'longitude' => -0.0561000,
            ],
            [
                'postcode' => 'E1 0BJ',
                'address_line_1' => 'Flat 2, Brockmer House',
                'address_line_2' => 'Crowder Street',
                'building_name' => 'Brockmer House',
                'building_number' => 'Flat 2',
                'street' => 'Crowder Street',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5112000,
                'longitude' => -0.0561000,
            ],
            [
                'postcode' => 'E1 0BJ',
                'address_line_1' => 'Flat 14, Brockmer House',
                'address_line_2' => 'Crowder Street',
                'building_name' => 'Brockmer House',
                'building_number' => 'Flat 14',
                'street' => 'Crowder Street',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5112000,
                'longitude' => -0.0561000,
            ],
            [
                'postcode' => 'E1 0BJ',
                'address_line_1' => 'Flat 64, Brockmer House',
                'address_line_2' => 'Crowder Street',
                'building_name' => 'Brockmer House',
                'building_number' => 'Flat 64',
                'street' => 'Crowder Street',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5112000,
                'longitude' => -0.0561000,
            ],
            // SW1A 1AA (Westminster demo cluster)
            [
                'postcode' => 'SW1A 1AA',
                'address_line_1' => '100 Demo Residence',
                'address_line_2' => 'Spur Road',
                'building_name' => 'Demo Residence',
                'building_number' => '100',
                'street' => 'Spur Road',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5010091,
                'longitude' => -0.1415876,
            ],
            [
                'postcode' => 'SW1A 1AA',
                'address_line_1' => '102 Demo Residence',
                'address_line_2' => 'Spur Road',
                'building_name' => 'Demo Residence',
                'building_number' => '102',
                'street' => 'Spur Road',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5010091,
                'longitude' => -0.1415876,
            ],
            // EC1A 1BB (City of London demo cluster)
            [
                'postcode' => 'EC1A 1BB',
                'address_line_1' => '12 St Martin Demo Court',
                'address_line_2' => 'St Martin-le-Grand',
                'building_name' => 'St Martin Demo Court',
                'building_number' => '12',
                'street' => 'St Martin-le-Grand',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5159000,
                'longitude' => -0.0972000,
            ],
            [
                'postcode' => 'EC1A 1BB',
                'address_line_1' => '14 St Martin Demo Court',
                'address_line_2' => 'St Martin-le-Grand',
                'building_name' => 'St Martin Demo Court',
                'building_number' => '14',
                'street' => 'St Martin-le-Grand',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5159000,
                'longitude' => -0.0972000,
            ],
            // W1A 1AA (West End demo cluster)
            [
                'postcode' => 'W1A 1AA',
                'address_line_1' => 'Suite 1, Portland Demo House',
                'address_line_2' => 'Portland Place',
                'building_name' => 'Portland Demo House',
                'building_number' => 'Suite 1',
                'street' => 'Portland Place',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5186000,
                'longitude' => -0.1438000,
            ],
            [
                'postcode' => 'W1A 1AA',
                'address_line_1' => 'Suite 2, Portland Demo House',
                'address_line_2' => 'Portland Place',
                'building_name' => 'Portland Demo House',
                'building_number' => 'Suite 2',
                'street' => 'Portland Place',
                'city' => 'London',
                'county' => 'Greater London',
                'latitude' => 51.5186000,
                'longitude' => -0.1438000,
            ],
            // M1 1AE (Manchester demo cluster)
            [
                'postcode' => 'M1 1AE',
                'address_line_1' => '25 Piccadilly Demo Suites',
                'address_line_2' => 'Piccadilly Gardens',
                'building_name' => 'Piccadilly Demo Suites',
                'building_number' => '25',
                'street' => 'Piccadilly Gardens',
                'city' => 'Manchester',
                'county' => 'Greater Manchester',
                'latitude' => 53.4809500,
                'longitude' => -2.2369000,
            ],
            [
                'postcode' => 'M1 1AE',
                'address_line_1' => '27 Piccadilly Demo Suites',
                'address_line_2' => 'Piccadilly Gardens',
                'building_name' => 'Piccadilly Demo Suites',
                'building_number' => '27',
                'street' => 'Piccadilly Gardens',
                'city' => 'Manchester',
                'county' => 'Greater Manchester',
                'latitude' => 53.4809500,
                'longitude' => -2.2369000,
            ],
        ];

        foreach ($demoAddresses as $item) {
            $item['country'] = 'United Kingdom';
            $item['source'] = 'demo_seeder';
            $item['is_active'] = true;
            $item['is_demo'] = true;

            $hash = UkAddress::generateDedupHash($item);

            UkAddress::updateOrCreate(
                ['dedup_hash' => $hash],
                $item
            );
        }
    }
}
