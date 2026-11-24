<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_return_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('country_code', 8)->nullable()->index();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_return_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('refund_return_policies')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('rules_json');
            $table->text('public_text')->nullable();
            $table->text('legal_disclaimer_note')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('published_by_admin_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'version_number'], 'rrpv_policy_ver_unique');
            $table->index(['status', 'effective_at'], 'rrpv_status_eff_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('policy_version_id')
                ->nullable()
                ->after('assigned_store_id')
                ->constrained('refund_return_policy_versions')
                ->nullOnDelete();
        });

        Schema::create('customer_service_claims', function (Blueprint $table) {
            $table->id();
            $table->string('case_reference', 50)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('policy_version_id')->nullable()->constrained('refund_return_policy_versions')->nullOnDelete();
            $table->enum('claim_type', ['cancellation_refund', 'rewash', 'damage_missing']);
            $table->string('reason_code', 80);
            $table->text('description');
            $table->string('preferred_resolution', 60)->nullable();
            $table->string('status', 40)->default('submitted');
            $table->unsignedBigInteger('requested_amount_minor')->default(0);
            $table->unsignedBigInteger('approved_amount_minor')->default(0);
            $table->string('currency', 8)->default('GBP');
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->enum('liability_party', ['none', 'platform', 'store', 'delivery_person', 'shared'])->default('none');
            $table->unsignedBigInteger('store_liability_minor')->default(0);
            $table->unsignedBigInteger('dp_liability_minor')->default(0);
            $table->string('rewash_status', 40)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'claim_type'], 'csc_booking_type_idx');
            $table->index(['status', 'created_at'], 'csc_status_created_idx');
            $table->index(['user_id', 'created_at'], 'csc_user_created_idx');
        });

        Schema::create('customer_service_claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('customer_service_claims')->cascadeOnDelete();
            $table->foreignId('booking_operational_item_id')->nullable()->constrained('booking_operational_items')->nullOnDelete();
            $table->string('service_name_snapshot', 190);
            $table->unsignedInteger('affected_quantity')->default(1);
            $table->unsignedBigInteger('claimed_amount_minor')->default(0);
            $table->unsignedBigInteger('approved_amount_minor')->default(0);
            $table->string('resolution_type', 60)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('claim_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('customer_service_claims')->cascadeOnDelete();
            $table->string('uploaded_by_type', 30);
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->string('disk', 40)->default('local');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        Schema::create('claim_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('customer_service_claims')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('actor_type', 30);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 150)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('refund_reference', 50)->unique();
            $table->foreignId('claim_id')->nullable()->constrained('customer_service_claims')->nullOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_customer_payment_id')->nullable()->constrained('booking_customer_payments')->nullOnDelete();
            $table->enum('payment_method', ['stripe', 'paypal', 'cod_manual', 'online_transfer']);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 8)->default('GBP');
            $table->enum('status', ['approved', 'awaiting_payout', 'processing', 'succeeded', 'paid', 'failed', 'reconciled'])->default('approved');
            $table->string('provider_refund_id', 150)->nullable()->index();
            $table->string('idempotency_key', 150)->unique();
            $table->string('payout_recipient', 190)->nullable();
            $table->string('payout_method', 80)->nullable();
            $table->string('external_reference', 150)->nullable();
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status'], 'rft_booking_status_idx');
            $table->index(['status', 'processed_at'], 'rft_status_proc_idx');
        });

        Schema::create('claim_delivery_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_reference', 50)->unique();
            $table->foreignId('claim_id')->constrained('customer_service_claims')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('delivery_person_id')->nullable()->constrained('delivery_persons')->nullOnDelete();
            $table->enum('job_type', ['rewash_collection', 'rewash_delivery']);
            $table->enum('status', ['pending', 'assigned', 'accepted', 'picked_up', 'store_received', 'completed', 'cancelled'])->default('pending');
            $table->text('pickup_location')->nullable();
            $table->text('destination_location')->nullable();
            $table->unsignedBigInteger('rate_minor')->default(0);
            $table->string('currency', 8)->default('GBP');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['claim_id', 'job_type'], 'cdj_claim_type_idx');
            $table->index(['delivery_person_id', 'status'], 'cdj_dp_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_delivery_jobs');
        Schema::dropIfExists('refund_transactions');
        Schema::dropIfExists('claim_status_history');
        Schema::dropIfExists('claim_evidence');
        Schema::dropIfExists('customer_service_claim_items');
        Schema::dropIfExists('customer_service_claims');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('policy_version_id');
        });
        Schema::dropIfExists('refund_return_policy_versions');
        Schema::dropIfExists('refund_return_policies');
    }
};
