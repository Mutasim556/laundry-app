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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Business name
            $table->string('owner_name');
            $table->string('phone', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('business_address')->nullable();
            $table->string('trade_license_number')->nullable();
            $table->string('trade_license_document')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->string('commission_type')->default('percentage'); // percentage or fixed
            $table->decimal('commission_rate', 8, 2)->default(0.00);
            $table->string('payout_method')->nullable(); // bKash, Nagad, Rocket, Bank Transfer
            $table->text('payout_details')->nullable(); // Account number, routing, bank branch
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
