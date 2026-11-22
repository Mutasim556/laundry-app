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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 120);
            $table->string('county', 120)->nullable();
            $table->string('postcode', 12)->index();
            $table->string('postcode_compact', 10)->index();
            $table->string('country', 100)->default('United Kingdom');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('source', 80)->nullable();
            $table->string('dedup_hash', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();

            $table->index(['is_active', 'name'], 'hotels_active_name_idx');
            $table->index(['postcode_compact', 'is_active'], 'hotels_compact_active_idx');
            $table->index(['latitude', 'longitude'], 'hotels_coords_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
