<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_store_pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference', 60)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('delivery_assignment_id')->constrained('delivery_assignments')->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'collected', 'completed'])->default('pending');
            $table->foreignId('requested_by_store_user_id')->nullable()->constrained('store_users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('request_note', 500)->nullable();
            $table->string('proof_of_delivery_notes', 500)->nullable();
            $table->unsignedInteger('booking_version')->nullable();
            $table->string('idempotency_key', 150)->unique();
            $table->timestamps();

            $table->index(['booking_id', 'status'], 'bspr_booking_status_idx');
            $table->index(['delivery_person_id', 'status', 'requested_at'], 'bspr_dp_status_req_idx');
            $table->index(['delivery_assignment_id', 'status'], 'bspr_asg_status_idx');
            $table->index(['store_id', 'status'], 'bspr_store_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_store_pickup_requests');
    }
};
