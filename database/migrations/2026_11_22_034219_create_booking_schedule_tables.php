<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_time_slots', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['collection', 'delivery'])->index();
            $table->string('name', 100)->nullable();
            $table->string('label', 60);
            $table->string('start_time', 5); // HH:MM (24h)
            $table->string('end_time', 5);   // HH:MM (24h)
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order'], 'bts_type_active_sort_idx');
        });

        Schema::create('booking_time_slot_weekdays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_slot_id')->constrained('booking_time_slots')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday .. 6 = Saturday
            $table->unique(['time_slot_id', 'weekday'], 'btsw_slot_weekday_unique');
        });

        Schema::create('booking_driver_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('description', 500)->nullable();
            $table->enum('type', ['collection', 'delivery', 'both'])->default('both')->index();
            $table->json('allowed_address_types')->nullable(); // e.g. ["HOME","OFFICE","HOTEL"]
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order'], 'bdi_type_active_sort_idx');
        });

        Schema::create('booking_availability_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->enum('type', ['collection', 'delivery', 'both'])->default('both');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['date', 'type'], 'bbd_date_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_blocked_dates');
        Schema::dropIfExists('booking_availability_settings');
        Schema::dropIfExists('booking_driver_instructions');
        Schema::dropIfExists('booking_time_slot_weekdays');
        Schema::dropIfExists('booking_time_slots');
    }
};
