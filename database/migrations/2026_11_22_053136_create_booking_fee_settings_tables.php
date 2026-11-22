<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->string('fee_code', 64)->unique();
            $table->string('display_name', 120);
            $table->string('fee_type', 20)->default('fixed');
            $table->decimal('fee_value', 10, 2)->default(0);
            $table->unsignedBigInteger('fee_value_minor')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_free')->default(false);
            $table->boolean('threshold_enabled')->default(false);
            $table->unsignedBigInteger('threshold_amount_minor')->default(0);
            $table->boolean('threshold_after_discount')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_fee_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fee_code', 64)->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 120)->nullable();
            $table->string('action', 64);
            $table->string('summary', 255);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'service_fee_minor')) {
                    $table->unsignedBigInteger('service_fee_minor')->default(0)->after('estimated_amount_minor');
                }
                if (! Schema::hasColumn('bookings', 'collection_delivery_fee_minor')) {
                    $table->unsignedBigInteger('collection_delivery_fee_minor')->default(0)->after('service_fee_minor');
                }
                if (! Schema::hasColumn('bookings', 'fee_snapshot')) {
                    $table->json('fee_snapshot')->nullable()->after('collection_delivery_fee_minor');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $dropCols = [];
                foreach (['service_fee_minor', 'collection_delivery_fee_minor', 'fee_snapshot'] as $col) {
                    if (Schema::hasColumn('bookings', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        Schema::dropIfExists('booking_fee_audit_logs');
        Schema::dropIfExists('booking_fee_settings');
    }
};
