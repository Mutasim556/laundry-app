<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\Hotel;

class HotelDemoSeeder extends Seeder
{
    /**
     * Seed clearly-labeled DEMO UK hotel records for development and testing.
     */
    public function run(): void
    {
        $hotels = [
            [
                'name' => 'Grand Royal Demo Hotel',
                'address_line_1' => '100 Whitechapel Road',
                'address_line_2' => 'Whitechapel',
                'city' => 'London',
                'county' => 'Greater London',
                'postcode' => 'E1 1JG',
                'latitude' => 51.5182000,
                'longitude' => -0.0628000,
                'phone' => '+44 20 7946 0101',
            ],
            [
                'name' => 'Grand Central Demo Hotel',
                'address_line_1' => '250 Euston Road',
                'address_line_2' => 'Bloomsbury',
                'city' => 'London',
                'county' => 'Greater London',
                'postcode' => 'NW1 2PG',
                'latitude' => 51.5263000,
                'longitude' => -0.1342000,
                'phone' => '+44 20 7946 0102',
            ],
            [
                'name' => 'The Whitechapel Demo Inn',
                'address_line_1' => '45 Commercial Road',
                'address_line_2' => 'Stepney',
                'city' => 'London',
                'county' => 'Greater London',
                'postcode' => 'E1 1LA',
                'latitude' => 51.5145000,
                'longitude' => -0.0679000,
                'phone' => '+44 20 7946 0103',
            ],
            [
                'name' => 'Westminster Park Demo Hotel',
                'address_line_1' => '200 Westminster Bridge Road',
                'address_line_2' => 'Lambeth',
                'city' => 'London',
                'county' => 'Greater London',
                'postcode' => 'SE1 7UT',
                'latitude' => 51.5005000,
                'longitude' => -0.1168000,
                'phone' => '+44 20 7946 0104',
            ],
            [
                'name' => 'Manchester Piccadilly Demo Hotel',
                'address_line_1' => '18 London Road',
                'address_line_2' => 'Piccadilly',
                'city' => 'Manchester',
                'county' => 'Greater Manchester',
                'postcode' => 'M1 2PF',
                'latitude' => 53.4774000,
                'longitude' => -2.2309000,
                'phone' => '+44 161 496 0105',
            ],
        ];

        foreach ($hotels as $item) {
            $item['country'] = 'United Kingdom';
            $item['source'] = 'demo_seeder';
            $item['is_active'] = true;
            $item['is_demo'] = true;

            $hash = Hotel::generateDedupHash($item);

            Hotel::updateOrCreate(
                ['dedup_hash' => $hash],
                $item
            );
        }
    }
}
