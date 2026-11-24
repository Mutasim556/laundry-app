<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_operational_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('item_category_id')->constrained('item_categories')->cascadeOnDelete();
            $table->string('category_name', 150);
            $table->string('origin_type', 30)->default('unknown'); // customer, admin, delivery_person, unknown
            $table->unsignedBigInteger('origin_actor_id')->nullable();
            $table->boolean('is_removed')->default(false);
            $table->string('removed_by_type', 30)->nullable();
            $table->unsignedBigInteger('removed_by_id')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->string('removal_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'item_category_id'], 'boc_booking_category_unique');
            $table->index(['booking_id', 'is_removed']);
        });

        Schema::table('booking_operational_items', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_operational_items', 'origin_type')) {
                $table->string('origin_type', 30)->nullable()->after('is_price_overridden');
            }
            if (! Schema::hasColumn('booking_operational_items', 'origin_actor_id')) {
                $table->unsignedBigInteger('origin_actor_id')->nullable()->after('origin_type');
            }
        });

        // Backfill customer-origin categories from immutable services_snapshot; unknown origins remain protected.
        $bookings = DB::table('bookings')->select('id', 'services_snapshot')->get();
        foreach ($bookings as $booking) {
            $snapshot = json_decode((string) $booking->services_snapshot, true);
            if (! is_array($snapshot)) {
                continue;
            }
            foreach ($snapshot as $snap) {
                $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : 0;
                if ($catId <= 0) {
                    continue;
                }
                $exists = DB::table('booking_operational_categories')
                    ->where('booking_id', $booking->id)
                    ->where('item_category_id', $catId)
                    ->exists();
                if (! $exists && DB::table('item_categories')->where('id', $catId)->exists()) {
                    DB::table('booking_operational_categories')->insert([
                        'booking_id' => $booking->id,
                        'item_category_id' => $catId,
                        'category_name' => (string) ($snap['name'] ?? 'Service Category'),
                        'origin_type' => 'customer',
                        'origin_actor_id' => null,
                        'is_removed' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_operational_categories');
    }
};
