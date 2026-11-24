<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'assigned_store_id')) {
                $table->foreignId('assigned_store_id')
                    ->nullable()
                    ->after('booking_status')
                    ->constrained('stores')
                    ->nullOnDelete();
            }
        });

        Schema::create('booking_store_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->boolean('is_current')->default(true);
            $table->enum('status', ['assigned', 'reassigned', 'cancelled'])->default('assigned');
            $table->string('customer_postcode', 20)->nullable();
            $table->string('store_postcode', 20)->nullable();
            $table->boolean('postcode_matched')->default(false);
            $table->text('override_reason')->nullable();
            $table->text('reassignment_reason')->nullable();

            // Public operational store snapshot at assignment time
            $table->string('store_name_snapshot', 255);
            $table->string('store_code_snapshot', 50)->nullable();
            $table->text('store_address_snapshot');
            $table->string('store_city_snapshot', 120)->nullable();
            $table->string('store_postcode_snapshot', 20)->nullable();
            $table->string('store_phone_snapshot', 40)->nullable();

            $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
            $table->timestamp('assigned_at');
            $table->unsignedBigInteger('superseded_by_admin_id')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'is_current'], 'bsa_booking_current_idx');
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_store_assignments');

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'assigned_store_id')) {
                $table->dropConstrainedForeignId('assigned_store_id');
            }
        });
    }
};
