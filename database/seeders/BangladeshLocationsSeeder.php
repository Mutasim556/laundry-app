<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Upazila;

class BangladeshLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 8 Divisions
        $divisions = [
            'Dhaka' => [
                'bn_name' => 'ঢাকা',
                'slug' => 'dhaka',
                'districts' => [
                    'Dhaka' => [
                        'bn_name' => 'ঢাকা',
                        'slug' => 'dhaka',
                        'upazilas' => [
                            'Dhanmondi' => [
                                'bn_name' => 'ধানমন্ডি',
                                'slug' => 'dhanmondi',
                                'areas' => [
                                    ['name' => 'Dhanmondi R/A', 'bn_name' => 'ধানমন্ডি আ/এ', 'postcode' => '1205', 'slug' => 'dhanmondi-ra'],
                                    ['name' => 'Kalabagan', 'bn_name' => 'কলাবাগান', 'postcode' => '1205', 'slug' => 'kalabagan'],
                                    ['name' => 'Sobhanbag', 'bn_name' => 'সোবহানবাগ', 'postcode' => '1207', 'slug' => 'sobhanbag'],
                                ],
                            ],
                            'Gulshan' => [
                                'bn_name' => 'গুলশান',
                                'slug' => 'gulshan',
                                'areas' => [
                                    ['name' => 'Gulshan 1', 'bn_name' => 'গুলশান ১', 'postcode' => '1212', 'slug' => 'gulshan-1'],
                                    ['name' => 'Gulshan 2', 'bn_name' => 'গুলশান ২', 'postcode' => '1212', 'slug' => 'gulshan-2'],
                                    ['name' => 'Niketan', 'bn_name' => 'নিকেতন', 'postcode' => '1212', 'slug' => 'niketan'],
                                ],
                            ],
                            'Banani' => [
                                'bn_name' => 'বনানী',
                                'slug' => 'banani',
                                'areas' => [
                                    ['name' => 'Banani DOHS', 'bn_name' => 'বনানী ডিওএইচএস', 'postcode' => '1213', 'slug' => 'banani-dohs'],
                                    ['name' => 'Chairman Bari', 'bn_name' => 'চেয়ারম্যান বাড়ি', 'postcode' => '1213', 'slug' => 'chairman-bari'],
                                ],
                            ],
                            'Uttara' => [
                                'bn_name' => 'উত্তরা',
                                'slug' => 'uttara',
                                'areas' => [
                                    ['name' => 'Uttara Sector 3', 'bn_name' => 'উত্তরা সেক্টর ৩', 'postcode' => '1230', 'slug' => 'uttara-sector-3'],
                                    ['name' => 'Uttara Sector 7', 'bn_name' => 'উত্তরা সেক্টর ৭', 'postcode' => '1230', 'slug' => 'uttara-sector-7'],
                                    ['name' => 'Uttara Sector 11', 'bn_name' => 'উত্তরা সেক্টর ১১', 'postcode' => '1230', 'slug' => 'uttara-sector-11'],
                                ],
                            ],
                            'Mirpur' => [
                                'bn_name' => 'মিরপুর',
                                'slug' => 'mirpur',
                                'areas' => [
                                    ['name' => 'Mirpur-1', 'bn_name' => 'মিরপুর-১', 'postcode' => '1216', 'slug' => 'mirpur-1'],
                                    ['name' => 'Mirpur-10', 'bn_name' => 'মিরপুর-১০', 'postcode' => '1216', 'slug' => 'mirpur-10'],
                                    ['name' => 'Pallabi', 'bn_name' => 'পল্লবী', 'postcode' => '1216', 'slug' => 'pallabi'],
                                ],
                            ],
                            'Mohammadpur' => [
                                'bn_name' => 'মোহাম্মদপুর',
                                'slug' => 'mohammadpur',
                                'areas' => [
                                    ['name' => 'Lalmatia', 'bn_name' => 'লালমাটিয়া', 'postcode' => '1207', 'slug' => 'lalmatia'],
                                    ['name' => 'Japan Garden City', 'bn_name' => 'জাপান গার্ডেন সিটি', 'postcode' => '1207', 'slug' => 'japan-garden-city'],
                                    ['name' => 'Town Hall', 'bn_name' => 'টাউন হল', 'postcode' => '1207', 'slug' => 'town-hall'],
                                ],
                            ],
                        ],
                    ],
                    'Gazipur' => [
                        'bn_name' => 'গাজীপুর',
                        'slug' => 'gazipur',
                        'upazilas' => [
                            'Gazipur Sadar' => [
                                'bn_name' => 'গাজীপুর সদর',
                                'slug' => 'gazipur-sadar',
                                'areas' => [
                                    ['name' => 'Joydebpur', 'bn_name' => 'জয়দেবপুর', 'postcode' => '1700', 'slug' => 'joydebpur'],
                                    ['name' => 'Board Bazar', 'bn_name' => 'বোর্ড বাজার', 'postcode' => '1704', 'slug' => 'board-bazar'],
                                ],
                            ],
                            'Tongi' => [
                                'bn_name' => 'টঙ্গী',
                                'slug' => 'tongi',
                                'areas' => [
                                    ['name' => 'Tongi Bazar', 'bn_name' => 'টঙ্গী বাজার', 'postcode' => '1710', 'slug' => 'tongi-bazar'],
                                    ['name' => 'Cherag Ali', 'bn_name' => 'চেরাগ আলী', 'postcode' => '1711', 'slug' => 'cherag-ali'],
                                ],
                            ],
                        ],
                    ],
                    'Narayanganj' => [
                        'bn_name' => 'নারায়ণগঞ্জ',
                        'slug' => 'narayanganj',
                        'upazilas' => [
                            'Narayanganj Sadar' => [
                                'bn_name' => 'নারায়ণগঞ্জ সদর',
                                'slug' => 'narayanganj-sadar',
                                'areas' => [
                                    ['name' => 'Chashara', 'bn_name' => 'চাষাড়া', 'postcode' => '1400', 'slug' => 'chashara'],
                                    ['name' => 'Deobhog', 'bn_name' => 'দেওভোগ', 'postcode' => '1400', 'slug' => 'deobhog'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Chattogram' => [
                'bn_name' => 'চট্টগ্রাম',
                'slug' => 'chattogram',
                'districts' => [
                    'Chattogram' => [
                        'bn_name' => 'চট্টগ্রাম',
                        'slug' => 'chattogram',
                        'upazilas' => [
                            'Kotwali' => [
                                'bn_name' => 'কোতোয়ালী',
                                'slug' => 'kotwali',
                                'areas' => [
                                    ['name' => 'GEC Circle', 'bn_name' => 'জিইসি মোড়', 'postcode' => '4000', 'slug' => 'gec-circle'],
                                    ['name' => 'Agrabad C/A', 'bn_name' => 'আগ্রাবাদ সি/এ', 'postcode' => '4100', 'slug' => 'agrabad-ca'],
                                ],
                            ],
                            'Panchlaish' => [
                                'bn_name' => 'পাঁচলাইশ',
                                'slug' => 'panchlaish',
                                'areas' => [
                                    ['name' => 'Nasirabad', 'bn_name' => 'নাসিরাবাদ', 'postcode' => '4203', 'slug' => 'nasirabad'],
                                    ['name' => 'Khulshi', 'bn_name' => 'খুলশী', 'postcode' => '4225', 'slug' => 'khulshi'],
                                ],
                            ],
                        ],
                    ],
                    'Cox\'s Bazar' => [
                        'bn_name' => 'কক্সবাজার',
                        'slug' => 'coxs-bazar',
                        'upazilas' => [
                            'Cox\'s Bazar Sadar' => [
                                'bn_name' => 'কক্সবাজার সদর',
                                'slug' => 'coxs-bazar-sadar',
                                'areas' => [
                                    ['name' => 'Kolatoli Beach Road', 'bn_name' => 'কলাতলী বিচ রোড', 'postcode' => '4700', 'slug' => 'kolatoli-beach-road'],
                                    ['name' => 'Laboni Point', 'bn_name' => 'লাবণী পয়েন্ট', 'postcode' => '4700', 'slug' => 'laboni-point'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Sylhet' => [
                'bn_name' => 'সিলেট',
                'slug' => 'sylhet',
                'districts' => [
                    'Sylhet' => [
                        'bn_name' => 'সিলেট',
                        'slug' => 'sylhet',
                        'upazilas' => [
                            'Sylhet Sadar' => [
                                'bn_name' => 'সিলেট সদর',
                                'slug' => 'sylhet-sadar',
                                'areas' => [
                                    ['name' => 'Zindabazar', 'bn_name' => 'জিন্দাবাজার', 'postcode' => '3100', 'slug' => 'zindabazar'],
                                    ['name' => 'Amberkhana', 'bn_name' => 'আম্বরখানা', 'postcode' => '3100', 'slug' => 'amberkhana'],
                                    ['name' => 'Upashahar', 'bn_name' => 'উপশহর', 'postcode' => '3100', 'slug' => 'upashahar'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Rajshahi' => [
                'bn_name' => 'রাজশাহী',
                'slug' => 'rajshahi',
                'districts' => [
                    'Rajshahi' => [
                        'bn_name' => 'রাজশাহী',
                        'slug' => 'rajshahi',
                        'upazilas' => [
                            'Boalia' => [
                                'bn_name' => 'বোয়ালিয়া',
                                'slug' => 'boalia',
                                'areas' => [
                                    ['name' => 'Shaheb Bazar', 'bn_name' => 'সাহেব বাজার', 'postcode' => '6000', 'slug' => 'shaheb-bazar'],
                                    ['name' => 'Motihar', 'bn_name' => 'মতিহার', 'postcode' => '6205', 'slug' => 'motihar'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Khulna' => [
                'bn_name' => 'খুলনা',
                'slug' => 'khulna',
                'districts' => [
                    'Khulna' => [
                        'bn_name' => 'খুলনা',
                        'slug' => 'khulna',
                        'upazilas' => [
                            'Khulna Sadar' => [
                                'bn_name' => 'খুলনা সদর',
                                'slug' => 'khulna-sadar',
                                'areas' => [
                                    ['name' => 'Sonadanga', 'bn_name' => 'সোনাডাঙ্গা', 'postcode' => '9000', 'slug' => 'sonadanga'],
                                    ['name' => 'Boyra', 'bn_name' => 'বয়রা', 'postcode' => '9000', 'slug' => 'boyra'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Barishal' => [
                'bn_name' => 'বরিশাল',
                'slug' => 'barishal',
                'districts' => [
                    'Barishal' => [
                        'bn_name' => 'বরিশাল',
                        'slug' => 'barishal',
                        'upazilas' => [
                            'Barishal Sadar' => [
                                'bn_name' => 'বরিশাল সদর',
                                'slug' => 'barishal-sadar',
                                'areas' => [
                                    ['name' => 'Band Road', 'bn_name' => 'বাঁধ রোড', 'postcode' => '8200', 'slug' => 'band-road'],
                                    ['name' => 'Natun Bazar', 'bn_name' => 'নতুন বাজার', 'postcode' => '8200', 'slug' => 'natun-bazar'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Rangpur' => [
                'bn_name' => 'রংপুর',
                'slug' => 'rangpur',
                'districts' => [
                    'Rangpur' => [
                        'bn_name' => 'রংপুর',
                        'slug' => 'rangpur',
                        'upazilas' => [
                            'Rangpur Sadar' => [
                                'bn_name' => 'রংপুর সদর',
                                'slug' => 'rangpur-sadar',
                                'areas' => [
                                    ['name' => 'Jahaj Company Mor', 'bn_name' => 'জাহাজ কোম্পানী মোড়', 'postcode' => '5400', 'slug' => 'jahaj-company-mor'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Mymensingh' => [
                'bn_name' => 'ময়মনসিংহ',
                'slug' => 'mymensingh',
                'districts' => [
                    'Mymensingh' => [
                        'bn_name' => 'ময়মনসিংহ',
                        'slug' => 'mymensingh',
                        'upazilas' => [
                            'Mymensingh Sadar' => [
                                'bn_name' => 'ময়মনসিংহ সদর',
                                'slug' => 'mymensingh-sadar',
                                'areas' => [
                                    ['name' => 'Ganginar Par', 'bn_name' => 'গাঙ্গিনার পাড়', 'postcode' => '2200', 'slug' => 'ganginar-par'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($divisions as $divName => $divData) {
            $division = Division::updateOrCreate(
                ['slug' => $divData['slug']],
                ['name' => $divName, 'bn_name' => $divData['bn_name'], 'is_active' => true]
            );

            if (!empty($divData['districts'])) {
                foreach ($divData['districts'] as $distName => $distData) {
                    $district = District::updateOrCreate(
                        ['division_id' => $division->id, 'slug' => $distData['slug']],
                        ['name' => $distName, 'bn_name' => $distData['bn_name'], 'is_active' => true]
                    );

                    if (!empty($distData['upazilas'])) {
                        foreach ($distData['upazilas'] as $upaName => $upaData) {
                            $upazila = Upazila::updateOrCreate(
                                ['district_id' => $district->id, 'slug' => $upaData['slug']],
                                ['name' => $upaName, 'bn_name' => $upaData['bn_name'], 'is_active' => true]
                            );

                            if (!empty($upaData['areas'])) {
                                foreach ($upaData['areas'] as $areaData) {
                                    LocalArea::updateOrCreate(
                                        ['upazila_id' => $upazila->id, 'slug' => $areaData['slug']],
                                        [
                                            'name' => $areaData['name'],
                                            'bn_name' => $areaData['bn_name'],
                                            'postcode' => $areaData['postcode'],
                                            'is_active' => true,
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
