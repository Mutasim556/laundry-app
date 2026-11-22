<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (! Schema::hasColumn('services', 'currency_code')) {
                    $table->string('currency_code', 3)->default('BDT')->after('price');
                }
                if (! Schema::hasColumn('services', 'original_currency')) {
                    $table->string('original_currency', 3)->nullable()->after('currency_code');
                }
                if (! Schema::hasColumn('services', 'original_amount')) {
                    $table->decimal('original_amount', 10, 2)->nullable()->after('original_currency');
                }
            });

            DB::table('services')
                ->whereNull('original_currency')
                ->update([
                    'currency_code' => 'BDT',
                    'original_currency' => 'BDT',
                    'original_amount' => DB::raw('price'),
                ]);
        }

        if (Schema::hasTable('booking_fee_settings')) {
            Schema::table('booking_fee_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('booking_fee_settings', 'currency_code')) {
                    $table->string('currency_code', 3)->default('BDT')->after('fee_value_minor');
                }
                if (! Schema::hasColumn('booking_fee_settings', 'original_currency')) {
                    $table->string('original_currency', 3)->nullable()->after('currency_code');
                }
                if (! Schema::hasColumn('booking_fee_settings', 'original_amount')) {
                    $table->decimal('original_amount', 10, 2)->nullable()->after('original_currency');
                }
            });

            DB::table('booking_fee_settings')
                ->whereNull('original_currency')
                ->update([
                    'currency_code' => 'BDT',
                    'original_currency' => 'BDT',
                    'original_amount' => DB::raw('fee_value'),
                ]);
        }

        Schema::create('currency_change_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 120)->nullable();
            $table->string('from_country', 120);
            $table->string('to_country', 120);
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->string('repricing_policy', 32);
            $table->decimal('exchange_rate', 14, 6)->nullable();
            $table->string('rate_source', 120)->nullable();
            $table->unsignedInteger('affected_services_count')->default(0);
            $table->string('summary', 255);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('settings')) {
            $defaults = [
                'business_country' => 'Bangladesh',
                'business_country_code' => 'BD',
                'default_currency' => 'BDT',
                'currency_symbol' => '৳',
                'currency_locale' => 'en-BD',
                'currency_decimals' => '2',
            ];

            foreach ($defaults as $key => $value) {
                if (! DB::table('settings')->where('key', $key)->exists()) {
                    DB::table('settings')->insert([
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_change_audit_logs');

        foreach (['services', 'booking_fee_settings'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $dropCols = [];
                    foreach (['currency_code', 'original_currency', 'original_amount'] as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $dropCols[] = $col;
                        }
                    }
                    if ($dropCols !== []) {
                        $table->dropColumn($dropCols);
                    }
                });
            }
        }
    }
};
