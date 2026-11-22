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
        Schema::create('uk_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 12)->index();
            $table->string('postcode_compact', 10)->index();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('building_name')->nullable();
            $table->string('building_number', 50)->nullable();
            $table->string('street')->nullable();
            $table->string('city', 120);
            $table->string('county', 120)->nullable();
            $table->string('country', 100)->default('United Kingdom');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('external_reference')->nullable();
            $table->string('source', 80)->nullable();
            $table->string('dedup_hash', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();

            $table->index(['postcode_compact', 'is_active'], 'uk_addresses_compact_active_idx');
            $table->index(['postcode', 'is_active'], 'uk_addresses_postcode_active_idx');
            $table->index(['city', 'street'], 'uk_addresses_city_street_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uk_addresses');
    }
};
