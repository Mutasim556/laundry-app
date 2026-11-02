<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Upazila;
use Modules\Admin\Models\Vendor;

class StoreDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Fetch Location references
        $dhakaDiv = Division::where('slug', 'dhaka')->first();
        $dhakaDist = District::where('slug', 'dhaka')->first();
        $ctgDiv = Division::where('slug', 'chattogram')->first();
        $ctgDist = District::where('slug', 'chattogram')->first();

        $dhanmondiUpa = Upazila::where('slug', 'dhanmondi')->first();
        $gulshanUpa = Upazila::where('slug', 'gulshan')->first();
        $uttaraUpa = Upazila::where('slug', 'uttara')->first();
        $kotwaliUpa = Upazila::where('slug', 'kotwali')->first();

        $dhanmondiArea = LocalArea::where('slug', 'dhanmondi-ra')->first();
        $gulshanArea = LocalArea::where('slug', 'gulshan-1')->first();
        $uttaraArea = LocalArea::where('slug', 'uttara-sector-3')->first();
        $gecArea = LocalArea::where('slug', 'gec-circle')->first();

        // Admin manager
        $admin = Admin::first();
        $serviceIds = Service::pluck('id')->toArray();

        // 2. Demo Vendors
        $vendor1 = Vendor::updateOrCreate(
            ['email' => 'cleanpro@vendor.bd'],
            [
                'name' => 'CleanPro Laundry Services Ltd.',
                'owner_name' => 'Khandaker Rafiqul Islam',
                'phone' => '01711223344',
                'alt_phone' => '01911223344',
                'business_address' => 'Plot 45, Road 11, Block C, Banani Commercial Area, Dhaka',
                'trade_license_number' => 'TRAD/DNCC/092144/2025',
                'status' => 'active',
                'verification_notes' => 'Trade licence and physical factory verified in Tejgaon Industrial Area. Certified partner.',
                'commission_type' => 'percentage',
                'commission_rate' => 12.00,
                'payout_method' => 'Bank Transfer',
                'payout_details' => 'City Bank Ltd., Gulshan Branch, A/C: 1102938475001, Routing: 225272635',
            ]
        );

        $vendor2 = Vendor::updateOrCreate(
            ['email' => 'sparklewash@vendor.bd'],
            [
                'name' => 'Sparkle & Steam Dry Cleaners',
                'owner_name' => 'Farhana Yasmin',
                'phone' => '01819876543',
                'alt_phone' => '01552345678',
                'business_address' => 'Shop 14, GEC Shopping Complex, O.R. Nizam Road, Chattogram',
                'trade_license_number' => 'TRAD/CCC/045812/2026',
                'status' => 'active',
                'verification_notes' => 'Experienced garment care specialist in Chattogram city.',
                'commission_type' => 'fixed',
                'commission_rate' => 35.00,
                'payout_method' => 'bKash',
                'payout_details' => 'bKash Merchant: 01819876543',
            ]
        );

        $vendor3 = Vendor::updateOrCreate(
            ['email' => 'fabcare@vendor.bd'],
            [
                'name' => 'FabCare Premium Fabricators',
                'owner_name' => 'Mahmudul Hasan',
                'phone' => '01671002233',
                'business_address' => 'Sector 7, Main Road, Uttara Model Town, Dhaka',
                'trade_license_number' => 'TRAD/DNCC/118742/2026',
                'status' => 'pending',
                'verification_notes' => 'Application submitted. Pending inspection of dry cleaning solvent disposal compliance.',
                'commission_type' => 'percentage',
                'commission_rate' => 10.00,
                'payout_method' => 'Nagad',
                'payout_details' => 'Nagad: 01671002233',
            ]
        );

        // 3. Demo Stores

        // Store 1: Company-Owned Store in Dhanmondi
        Store::updateOrCreate(
            ['slug' => 'laundry-hub-dhanmondi'],
            [
                'ownership_type' => 'my_store',
                'vendor_id' => null,
                'admin_id' => $admin?->id,
                'status' => 'active',
                'name' => 'Laundry Hub - Dhanmondi Flagship',
                'bn_name' => 'লন্ড্রি হাব - ধানমন্ডি ফ্ল্যাগশিপ',
                'store_type' => 'both',
                'description' => 'Our flagship company-owned store featuring high-capacity commercial washers and premium steam irons.',
                'contact_person' => 'Md. Zahid Hassan (Store Supervisor)',
                'phone' => '01710998877',
                'alt_phone' => '029668877',
                'email' => 'dhanmondi@laundryhub.com.bd',
                'holding_no' => 'House 42/A',
                'road_no' => 'Road 9/A, Satmasjid Road',
                'nearby_landmark' => 'Near Dhanmondi Lake & Shimanto Square',
                'full_address' => 'House 42/A, Road 9/A, Satmasjid Road, Dhanmondi R/A, Dhaka-1205',
                'division_id' => $dhakaDiv?->id,
                'district_id' => $dhakaDist?->id,
                'upazila_id' => $dhanmondiUpa?->id,
                'local_area_id' => $dhanmondiArea?->id,
                'area_name' => 'Dhanmondi R/A',
                'postcode' => '1205',
                'service_ids' => $serviceIds,
                'pricing_model' => 'hybrid',
                'min_order_amount' => 150.00,
                'has_delivery' => true,
                'delivery_fee' => 40.00,
                'free_delivery_threshold' => 600.00,
                'turnaround_time' => '24-48 Hours (Express 12h available)',
                'is_accepting_orders' => true,
                'service_areas_text' => 'Dhanmondi, Kalabagan, Sobhanbag, Lalmatia, Mohammadpur, Shankar, Green Road',
            ]
        );

        // Store 2: Company-Owned Store in Uttara
        Store::updateOrCreate(
            ['slug' => 'laundry-hub-uttara'],
            [
                'ownership_type' => 'my_store',
                'vendor_id' => null,
                'admin_id' => $admin?->id,
                'status' => 'active',
                'name' => 'Laundry Hub - Uttara Branch',
                'bn_name' => 'লন্ড্রি হাব - উত্তরা শাখা',
                'store_type' => 'laundry',
                'description' => 'Fast everyday laundry and linen care for residential sectors in North Dhaka.',
                'contact_person' => 'Tariqul Islam',
                'phone' => '01710998866',
                'email' => 'uttara@laundryhub.com.bd',
                'holding_no' => 'Holding 18',
                'road_no' => 'Rabindra Sarani, Sector 3',
                'nearby_landmark' => 'Opposite to Friends Club Field',
                'full_address' => 'Holding 18, Rabindra Sarani, Sector 3, Uttara, Dhaka-1230',
                'division_id' => $dhakaDiv?->id,
                'district_id' => $dhakaDist?->id,
                'upazila_id' => $uttaraUpa?->id,
                'local_area_id' => $uttaraArea?->id,
                'area_name' => 'Uttara Sector 3',
                'postcode' => '1230',
                'service_ids' => array_slice($serviceIds, 0, 3),
                'pricing_model' => 'per_kg',
                'min_order_amount' => 200.00,
                'has_delivery' => true,
                'delivery_fee' => 50.00,
                'free_delivery_threshold' => 700.00,
                'turnaround_time' => '24 Hours',
                'is_accepting_orders' => true,
                'service_areas_text' => 'Uttara Sector 1 to 14, Airport area, Ashkona, Nikunja 1 & 2',
            ]
        );

        // Store 3: Vendor Store in Gulshan (Owned by CleanPro)
        Store::updateOrCreate(
            ['slug' => 'cleanpro-gulshan-luxe'],
            [
                'ownership_type' => 'vendor_store',
                'vendor_id' => $vendor1->id,
                'admin_id' => $admin?->id,
                'status' => 'active',
                'name' => 'CleanPro Luxe - Gulshan 1',
                'bn_name' => 'ক্লিনপ্রো লাক্স - গুলশান ১',
                'store_type' => 'both',
                'description' => 'Delicate fabric care, suit dry cleaning, silk pressing, and executive laundry services.',
                'contact_person' => 'Shahriar Khan',
                'phone' => '01711223399',
                'email' => 'gulshan@cleanpro.com.bd',
                'holding_no' => 'Navana Tower, Ground Floor',
                'road_no' => 'Gulshan Avenue',
                'nearby_landmark' => 'Beside Gulshan 1 Circle',
                'full_address' => 'Navana Tower, Ground Floor, Gulshan Avenue, Gulshan 1, Dhaka-1212',
                'division_id' => $dhakaDiv?->id,
                'district_id' => $dhakaDist?->id,
                'upazila_id' => $gulshanUpa?->id,
                'local_area_id' => $gulshanArea?->id,
                'area_name' => 'Gulshan 1',
                'postcode' => '1212',
                'service_ids' => $serviceIds,
                'pricing_model' => 'hybrid',
                'min_order_amount' => 300.00,
                'has_delivery' => true,
                'delivery_fee' => 60.00,
                'free_delivery_threshold' => 1000.00,
                'turnaround_time' => 'Same Day / 24 Hours',
                'is_accepting_orders' => true,
                'service_areas_text' => 'Gulshan 1, Gulshan 2, Niketan, Banani, Baridhara Diplomatic Zone',
                'commission_type' => 'percentage',
                'commission_rate' => 12.00,
                'verification_notes' => 'Premium setup with eco-friendly hydrocarbon dry cleaning equipment.',
            ]
        );

        // Store 4: Vendor Store in Chattogram GEC (Owned by Sparkle & Steam)
        Store::updateOrCreate(
            ['slug' => 'sparkle-steam-chattogram-gec'],
            [
                'ownership_type' => 'vendor_store',
                'vendor_id' => $vendor2->id,
                'admin_id' => $admin?->id,
                'status' => 'active',
                'name' => 'Sparkle & Steam - Chattogram GEC',
                'bn_name' => 'স্পার্কল অ্যান্ড স্টিম - চট্টগ্রাম জিইসি',
                'store_type' => 'dry_cleaning',
                'description' => 'Chattogram city specialist for sherwani, blazers, designer sarees, and bridal wear.',
                'contact_person' => 'Imtiaz Ahmed',
                'phone' => '01819876540',
                'email' => 'gec@sparklewash.com.bd',
                'holding_no' => 'Shop 14, GEC Complex',
                'road_no' => 'O.R. Nizam Road',
                'nearby_landmark' => 'GEC Circle, Central Chattogram',
                'full_address' => 'Shop 14, GEC Shopping Complex, O.R. Nizam Road, Kotwali, Chattogram-4000',
                'division_id' => $ctgDiv?->id,
                'district_id' => $ctgDist?->id,
                'upazila_id' => $kotwaliUpa?->id,
                'local_area_id' => $gecArea?->id,
                'area_name' => 'GEC Circle',
                'postcode' => '4000',
                'service_ids' => array_slice($serviceIds, 1, 4),
                'pricing_model' => 'per_item',
                'min_order_amount' => 250.00,
                'has_delivery' => true,
                'delivery_fee' => 50.00,
                'free_delivery_threshold' => 800.00,
                'turnaround_time' => '48 Hours',
                'is_accepting_orders' => true,
                'service_areas_text' => 'GEC Circle, Nasirabad, Khulshi, Agrabad C/A, Chawkbazar, Mehdibag',
                'commission_type' => 'fixed',
                'commission_rate' => 35.00,
                'verification_notes' => 'Physical store inspected on 15 Oct 2026. Steam press boilers checked.',
            ]
        );
    }
}
