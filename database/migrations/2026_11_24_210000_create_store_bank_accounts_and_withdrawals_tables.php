<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_transactions', function (Blueprint $table) {
            $table->string('transaction_type', 50)->change();
        });

        if (! Schema::hasTable('store_bank_accounts')) {
            Schema::create('store_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')
                    ->constrained('stores', 'id', 'store_bank_store_fk')
                    ->cascadeOnDelete();
                $table->string('account_holder_name', 160);
                $table->string('bank_name', 160);
                $table->string('country_code', 2)->default('GB');
                $table->string('currency', 3)->default('GBP');
                $table->string('account_type', 30)->nullable();
                $table->text('account_number_encrypted');
                $table->string('account_number_last4', 4);
                $table->string('account_number_masked', 40);
                $table->text('iban_encrypted')->nullable();
                $table->text('routing_code_encrypted')->nullable();
                $table->string('routing_code_label', 40)->nullable();
                $table->string('swift_bic', 20)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('verification_status', 30)->default('unverified');
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'is_active'], 'store_bank_active_idx');
            });
        }

        if (! Schema::hasTable('store_withdrawal_requests')) {
            Schema::create('store_withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->string('withdrawal_reference', 40)->unique();
                $table->foreignId('store_id')
                    ->constrained('stores', 'id', 'store_wdl_store_fk')
                    ->cascadeOnDelete();
                $table->foreignId('requested_by_store_user_id')
                    ->nullable()
                    ->constrained('store_users', 'id', 'store_wdl_user_fk')
                    ->nullOnDelete();
                $table->foreignId('bank_account_id')
                    ->constrained('store_bank_accounts', 'id', 'store_wdl_bank_fk');
                $table->json('bank_snapshot_json');
                $table->unsignedBigInteger('amount_minor');
                $table->string('currency', 3);
                $table->string('request_month', 7)->index();
                $table->unsignedBigInteger('available_balance_before_minor')->default(0);
                $table->string('status', 30)->default('pending')->index();
                $table->text('notes')->nullable();
                $table->string('idempotency_key', 80)->nullable()->unique();
                $table->foreignId('reservation_transaction_id')
                    ->nullable()
                    ->constrained('store_transactions', 'id', 'store_wdl_res_tx_fk')
                    ->nullOnDelete();
                $table->foreignId('release_transaction_id')
                    ->nullable()
                    ->constrained('store_transactions', 'id', 'store_wdl_rel_tx_fk')
                    ->nullOnDelete();
                $table->timestamp('requested_at');
                $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins', 'id', 'store_wdl_app_admin_fk')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by_admin_id')->nullable()->constrained('admins', 'id', 'store_wdl_rej_admin_fk')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('settled_by_admin_id')->nullable()->constrained('admins', 'id', 'store_wdl_set_admin_fk')->nullOnDelete();
                $table->timestamp('settled_at')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('external_reference', 120)->nullable()->unique();
                $table->text('settlement_notes')->nullable();
                $table->json('audit_history')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'request_month', 'status'], 'store_wdl_store_month_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_withdrawal_requests');
        Schema::dropIfExists('store_bank_accounts');
    }
};
