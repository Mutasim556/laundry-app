<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 190)->unique();
            $table->string('phone_country_code', 12)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_normalized', 40)->nullable()->unique();
            $table->string('password');
            $table->string('role', 40)->default('owner'); // owner, manager, staff
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        Schema::create('booking_store_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('booking_store_assignment_id')->nullable()->constrained('booking_store_assignments')->nullOnDelete();
            $table->string('currency', 10)->default('GBP');
            $table->unsignedBigInteger('eligible_service_amount_minor')->default(0);
            $table->string('commission_type', 30)->default('percentage');
            $table->decimal('commission_rate', 8, 2)->default(0.00);
            $table->unsignedBigInteger('commission_amount_minor')->default(0);
            $table->unsignedBigInteger('net_store_earnings_minor')->default(0);
            $table->boolean('is_current')->default(true);
            $table->string('snapshot_hash', 64);
            $table->unsignedBigInteger('finalized_by_admin_id')->nullable();
            $table->timestamp('finalized_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_current', 'finalized_at'], 'bse_store_current_fin_idx');
            $table->index(['booking_id', 'is_current'], 'bse_booking_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_store_earnings');
        Schema::dropIfExists('store_users');
    }
};
