<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            // 1. Store ownership & administration
            $table->enum('ownership_type', ['my_store', 'vendor_store'])->default('my_store');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete(); // Store administrator / manager
            $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('active');

            // 2. Basic store information
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('store_type')->default('laundry'); // laundry, dry_cleaning, or both

            // Address breakdown (nearby landmark, house/road/area)
            $table->string('holding_no')->nullable(); // House / Holding number
            $table->string('road_no')->nullable();    // Road name / number
            $table->string('nearby_landmark')->nullable(); // Landmark / near location
            $table->text('full_address');             // Complete street address

            // Opening days and hours (JSON array or object)
            // e.g. [{"day":"Saturday","open":"09:00","close":"21:00","is_closed":false},...]
            $table->json('opening_hours')->nullable();

            // 3. Bangladesh Location (Linked hierarchy)
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('upazila_id')->constrained('upazilas')->cascadeOnDelete();
            $table->foreignId('local_area_id')->nullable()->constrained('local_areas')->nullOnDelete();
            $table->string('area_name')->nullable(); // Fallback text when custom neighbourhood
            $table->string('postcode', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // 4. Service & Delivery Information
            // Array of service IDs or service slugs offered
            $table->json('service_ids')->nullable();
            $table->string('pricing_model')->default('per_item'); // per_kg, per_item, per_bag, hybrid
            $table->decimal('min_order_amount', 10, 2)->default(0.00); // in BDT (৳)
            $table->boolean('has_delivery')->default(true); // Pickup & Delivery available
            $table->decimal('delivery_fee', 10, 2)->default(0.00); // in BDT (৳)
            $table->decimal('free_delivery_threshold', 10, 2)->nullable(); // in BDT (৳)
            $table->string('turnaround_time')->nullable(); // e.g. 24-48 Hours, Same Day
            $table->boolean('is_accepting_orders')->default(true); // Accepting orders or temporarily unavailable

            // Service Areas (IDs or names of areas where this store collects & delivers)
            // Separate from physical store location
            $table->json('service_area_ids')->nullable(); // local_area_ids served
            $table->text('service_areas_text')->nullable(); // Freeform notes on service coverage

            // 5. Vendor-specific snapshot / notes (when ownership_type is vendor_store)
            $table->text('verification_notes')->nullable();
            $table->string('commission_type')->nullable(); // inherited or customized
            $table->decimal('commission_rate', 8, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
