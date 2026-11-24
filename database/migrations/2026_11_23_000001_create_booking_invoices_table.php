<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedInteger('revision_number')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('currency', 10)->default('GBP');
            $table->unsignedBigInteger('items_subtotal_minor')->default(0);
            $table->unsignedBigInteger('service_fee_minor')->default(0);
            $table->unsignedBigInteger('delivery_fee_minor')->default(0);
            $table->unsignedBigInteger('tip_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('final_total_minor')->default(0);
            $table->string('payment_gateway', 60)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->string('pricing_status_label', 60)->nullable();
            $table->json('business_snapshot');
            $table->json('customer_snapshot');
            $table->json('items_snapshot');
            $table->string('snapshot_hash', 64);
            $table->unsignedBigInteger('issued_by_admin_id')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['booking_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_invoices');
    }
};
