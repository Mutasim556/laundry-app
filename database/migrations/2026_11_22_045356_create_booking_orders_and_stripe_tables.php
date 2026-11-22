<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 160);
            $table->string('customer_email', 160)->index();
            $table->string('customer_phone', 40);
            $table->string('customer_type', 20)->default('individual');

            $table->json('address_snapshot');
            $table->json('schedule_snapshot');
            $table->json('services_snapshot');

            $table->string('payment_gateway', 40)->default('cod')->index();
            $table->string('payment_status', 40)->default('pending_item_count')->index();
            $table->string('currency', 3)->default('GBP');
            $table->unsignedBigInteger('estimated_amount_minor')->default(0);
            $table->unsignedBigInteger('final_amount_minor')->nullable();
            $table->unsignedBigInteger('tip_amount_minor')->default(0);

            $table->string('stripe_customer_id', 100)->nullable()->index();
            $table->string('stripe_payment_method_id', 100)->nullable();
            $table->string('stripe_setup_intent_id', 100)->nullable()->index();
            $table->string('stripe_payment_intent_id', 100)->nullable()->index();
            $table->string('stripe_refund_id', 100)->nullable();
            $table->string('refund_status', 40)->nullable();
            $table->unsignedBigInteger('refunded_amount_minor')->default(0);

            $table->boolean('consent_accepted')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->text('consent_text')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('last_payment_error')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('customer_email', 160)->index();
            $table->string('stripe_customer_id', 100)->index();
            $table->string('stripe_payment_method_id', 100)->unique();
            $table->string('card_brand', 30);
            $table->string('card_last4', 4);
            $table->unsignedTinyInteger('card_exp_month');
            $table->unsignedSmallInteger('card_exp_year');
            $table->boolean('is_default')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id', 120)->unique();
            $table->string('event_type', 100)->index();
            $table->json('payload_summary')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('customer_payment_methods');
        Schema::dropIfExists('bookings');
    }
};
