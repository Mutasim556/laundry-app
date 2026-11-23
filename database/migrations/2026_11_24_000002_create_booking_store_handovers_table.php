<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_store_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference', 60)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('collection_delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('accepted_by_store_user_id')->nullable()->constrained('store_users')->nullOnDelete();
            $table->foreignId('rejected_by_store_user_id')->nullable()->constrained('store_users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('request_note', 500)->nullable();
            $table->unsignedInteger('booking_version')->nullable();
            $table->string('idempotency_key', 150)->unique();
            $table->timestamps();

            $table->index(['store_id', 'status', 'requested_at'], 'bsh_store_status_req_idx');
            $table->index(['booking_id', 'status'], 'bsh_booking_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_store_handovers');
    }
};
