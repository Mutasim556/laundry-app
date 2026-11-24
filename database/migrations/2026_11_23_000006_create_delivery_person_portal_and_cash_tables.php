<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_persons', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_persons', 'portal_access_enabled')) {
                $table->boolean('portal_access_enabled')->default(false)->after('work_status');
            }
            if (! Schema::hasColumn('delivery_persons', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (! Schema::hasColumn('delivery_persons', 'phone_normalized')) {
                $table->string('phone_normalized', 40)->nullable()->index()->after('phone');
            }
            if (! Schema::hasColumn('delivery_persons', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (! Schema::hasColumn('delivery_persons', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('portal_access_enabled');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'collection_otp_verified_at')) {
                $table->timestamp('collection_otp_verified_at')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'collection_otp_verified_by_dp_id')) {
                $table->unsignedBigInteger('collection_otp_verified_by_dp_id')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'cash_collected_minor')) {
                $table->unsignedBigInteger('cash_collected_minor')->default(0)->after('paid_at');
            }
            if (! Schema::hasColumn('bookings', 'customer_payment_channel')) {
                $table->string('customer_payment_channel', 40)->nullable()->after('payment_gateway');
            }
        });

        Schema::create('booking_collection_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->string('purpose', 50)->default('collection_confirmation');
            $table->string('channel', 30)->default('email');
            $table->string('recipient', 190);
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_delivery_person_id')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'purpose', 'verified_at']);
        });

        Schema::create('booking_customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->nullable()->constrained('delivery_persons')->nullOnDelete();
            $table->string('payment_method', 40); // stripe, paypal, cash_in_hand, online_transfer
            $table->string('payment_channel', 40); // gateway, cash, bank_transfer
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('GBP');
            $table->string('status', 40)->default('pending'); // pending, awaiting_verification, confirmed, failed, refunded
            $table->string('external_reference', 190)->nullable();
            $table->string('receiving_account', 190)->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->string('idempotency_key', 150)->unique();
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->unsignedBigInteger('verified_by_admin_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });

        Schema::create('delivery_person_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_reference', 60)->unique();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('settlement_channel', 40)->default('cash_handover');
            $table->timestamp('settled_at');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 150)->unique();
            $table->timestamps();
        });

        Schema::create('delivery_person_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('delivery_person_settlements')->nullOnDelete();
            $table->string('transaction_type', 60);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->decimal('balance_after', 12, 2);
            $table->string('payment_channel', 40)->default('cash_in_hand');
            $table->string('reference', 60)->unique();
            $table->string('idempotency_key', 150)->unique();
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->string('description', 500);
            $table->string('created_by_type', 30)->default('system');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['delivery_person_id', 'currency', 'id'], 'dp_cash_tx_dp_curr_id_idx');
        });

        foreach (['view-delivery-settlements', 'manage-delivery-settlements'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_person_cash_transactions');
        Schema::dropIfExists('delivery_person_settlements');
        Schema::dropIfExists('booking_customer_payments');
        Schema::dropIfExists('booking_collection_otps');
    }
};
