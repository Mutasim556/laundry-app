<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            Schema::table('customer_addresses', function (Blueprint $table) {
                if (! Schema::hasColumn('customer_addresses', 'contact_type')) {
                    $table->string('contact_type', 20)->default('individual')->after('is_default');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_first_name')) {
                    $table->string('contact_first_name', 80)->nullable()->after('contact_type');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_last_name')) {
                    $table->string('contact_last_name', 80)->nullable()->after('contact_first_name');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_company_name')) {
                    $table->string('contact_company_name', 150)->nullable()->after('contact_last_name');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_tax_number')) {
                    $table->string('contact_tax_number', 80)->nullable()->after('contact_company_name');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_country_iso')) {
                    $table->string('contact_country_iso', 4)->nullable()->after('contact_tax_number');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_dialing_code')) {
                    $table->string('contact_dialing_code', 10)->nullable()->after('contact_country_iso');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_phone')) {
                    $table->string('contact_phone', 32)->nullable()->after('contact_dialing_code');
                }
                if (! Schema::hasColumn('customer_addresses', 'contact_email')) {
                    $table->string('contact_email', 160)->nullable()->after('contact_phone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            Schema::table('customer_addresses', function (Blueprint $table) {
                $dropCols = [];
                foreach ([
                    'contact_type',
                    'contact_first_name',
                    'contact_last_name',
                    'contact_company_name',
                    'contact_tax_number',
                    'contact_country_iso',
                    'contact_dialing_code',
                    'contact_phone',
                    'contact_email',
                ] as $col) {
                    if (Schema::hasColumn('customer_addresses', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }
    }
};
