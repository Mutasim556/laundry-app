<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds international, country-aware columns to stores and vendors
     * while preserving all existing records and making BD-specific columns nullable.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->change();
            }
            if (Schema::hasColumn('stores', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable()->change();
            }
            if (Schema::hasColumn('stores', 'upazila_id')) {
                $table->unsignedBigInteger('upazila_id')->nullable()->change();
            }
            if (Schema::hasColumn('stores', 'full_address')) {
                $table->text('full_address')->nullable()->change();
            }

            if (! Schema::hasColumn('stores', 'store_code')) {
                $table->string('store_code', 50)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('stores', 'phone_country_code')) {
                $table->string('phone_country_code', 10)->nullable()->after('contact_person');
            }
            if (! Schema::hasColumn('stores', 'alt_phone_country_code')) {
                $table->string('alt_phone_country_code', 10)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('stores', 'website')) {
                $table->string('website', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('stores', 'country_code')) {
                $table->string('country_code', 4)->nullable()->after('full_address');
            }
            if (! Schema::hasColumn('stores', 'country_name')) {
                $table->string('country_name', 120)->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('stores', 'address_line_1')) {
                $table->string('address_line_1', 255)->nullable()->after('country_name');
            }
            if (! Schema::hasColumn('stores', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('stores', 'city')) {
                $table->string('city', 120)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('stores', 'state_region')) {
                $table->string('state_region', 120)->nullable()->after('city');
            }
            if (! Schema::hasColumn('stores', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('turnaround_time');
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'vendor_code')) {
                $table->string('vendor_code', 50)->nullable()->after('id');
            }
            if (! Schema::hasColumn('vendors', 'business_type')) {
                $table->string('business_type', 80)->nullable()->after('owner_name');
            }
            if (! Schema::hasColumn('vendors', 'phone_country_code')) {
                $table->string('phone_country_code', 10)->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('vendors', 'alt_phone_country_code')) {
                $table->string('alt_phone_country_code', 10)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('vendors', 'country_code')) {
                $table->string('country_code', 4)->nullable()->after('email');
            }
            if (! Schema::hasColumn('vendors', 'country_name')) {
                $table->string('country_name', 120)->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('vendors', 'address_line_1')) {
                $table->string('address_line_1', 255)->nullable()->after('country_name');
            }
            if (! Schema::hasColumn('vendors', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('vendors', 'city')) {
                $table->string('city', 120)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('vendors', 'state_region')) {
                $table->string('state_region', 120)->nullable()->after('city');
            }
            if (! Schema::hasColumn('vendors', 'postcode')) {
                $table->string('postcode', 30)->nullable()->after('state_region');
            }
            if (! Schema::hasColumn('vendors', 'tax_vat_number')) {
                $table->string('tax_vat_number', 100)->nullable()->after('trade_license_number');
            }
            if (! Schema::hasColumn('vendors', 'description')) {
                $table->text('description')->nullable()->after('tax_vat_number');
            }
            if (! Schema::hasColumn('vendors', 'payment_terms')) {
                $table->string('payment_terms', 120)->nullable()->after('commission_rate');
            }
            if (! Schema::hasColumn('vendors', 'service_ids')) {
                $table->json('service_ids')->nullable()->after('payment_terms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = [
                'store_code', 'phone_country_code', 'alt_phone_country_code',
                'website', 'country_code', 'country_name', 'address_line_1',
                'address_line_2', 'city', 'state_region', 'capacity',
            ];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('stores', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            $columns = [
                'vendor_code', 'business_type', 'phone_country_code', 'alt_phone_country_code',
                'country_code', 'country_name', 'address_line_1', 'address_line_2',
                'city', 'state_region', 'postcode', 'tax_vat_number',
                'description', 'payment_terms', 'service_ids',
            ];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('vendors', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
