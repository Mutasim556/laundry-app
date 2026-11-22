<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_operational_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('item_category_id')->nullable()->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->unsignedBigInteger('item_id')->nullable()->index();
            $table->string('service_name', 160);
            $table->string('category_name', 160)->nullable();
            $table->string('item_name', 160)->nullable();
            $table->string('pricing_mode', 20)->default('item');
            $table->string('unit_name', 32)->default('item');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->unsignedBigInteger('catalog_unit_price_minor')->default(0);
            $table->unsignedBigInteger('actual_unit_price_minor')->default(0);
            $table->unsignedBigInteger('line_total_minor')->default(0);
            $table->boolean('is_price_overridden')->default(false);
            $table->string('adjustment_reason', 255)->nullable();
            $table->unsignedBigInteger('adjusted_by_admin_id')->nullable();
            $table->timestamp('adjusted_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('is_locked');
                }
                if (! Schema::hasColumn('bookings', 'service_fee_override_mode')) {
                    $table->string('service_fee_override_mode', 20)->default('default')->after('service_fee_minor');
                }
                if (! Schema::hasColumn('bookings', 'delivery_fee_override_mode')) {
                    $table->string('delivery_fee_override_mode', 20)->default('default')->after('collection_delivery_fee_minor');
                }
                if (! Schema::hasColumn('bookings', 'discount_minor')) {
                    $table->unsignedBigInteger('discount_minor')->default(0)->after('delivery_fee_override_mode');
                }
                if (! Schema::hasColumn('bookings', 'tax_minor')) {
                    $table->unsignedBigInteger('tax_minor')->default(0)->after('discount_minor');
                }
                if (! Schema::hasColumn('bookings', 'operational_notes')) {
                    $table->text('operational_notes')->nullable()->after('cancellation_reason');
                }
                if (! Schema::hasColumn('bookings', 'cancelled_by_admin_id')) {
                    $table->unsignedBigInteger('cancelled_by_admin_id')->nullable()->after('cancelled_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $dropCols = [];
                foreach ([
                    'version',
                    'service_fee_override_mode',
                    'delivery_fee_override_mode',
                    'discount_minor',
                    'tax_minor',
                    'operational_notes',
                    'cancelled_by_admin_id',
                ] as $col) {
                    if (Schema::hasColumn('bookings', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        Schema::dropIfExists('booking_operational_items');
    }
};
