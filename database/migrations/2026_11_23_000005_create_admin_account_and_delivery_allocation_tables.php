<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_assignments', 'tip_share_minor')) {
                $table->unsignedBigInteger('tip_share_minor')->default(0)->after('rate_minor');
            }
            if (! Schema::hasColumn('delivery_assignments', 'is_manual_rate_override')) {
                $table->boolean('is_manual_rate_override')->default(false)->after('tip_share_minor');
            }
        });

        Schema::table('delivery_person_ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_person_ledger_entries', 'idempotency_key')) {
                $table->string('idempotency_key', 140)->nullable()->unique()->after('entry_reference');
            }
        });

        Schema::create('admin_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 10)->unique();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->decimal('total_credited', 14, 2)->default(0.00);
            $table->decimal('total_debited', 14, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('admin_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_account_id')->constrained('admin_accounts')->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('transaction_type', 50); // delivery_margin_credit, delivery_shortfall_debit, adjustment, advance_settlement, reversal, platform_fee_revenue, commission_revenue, refund_adjustment
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10);
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->string('reference', 60)->unique();
            $table->string('idempotency_key', 140)->unique();
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->string('description', 500);
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['admin_account_id', 'created_at'], 'aat_account_created_idx');
            $table->index('booking_id', 'aat_booking_idx');
            $table->index('transaction_type', 'aat_type_idx');
        });

        Schema::create('booking_financial_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedInteger('revision_number')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('currency', 10);
            $table->unsignedBigInteger('delivery_fee_minor')->default(0);
            $table->unsignedBigInteger('customer_tip_minor')->default(0);
            $table->unsignedBigInteger('collection_delivery_payment_minor')->default(0);
            $table->unsignedBigInteger('final_delivery_payment_minor')->default(0);
            $table->unsignedBigInteger('collection_tip_share_minor')->default(0);
            $table->unsignedBigInteger('final_tip_share_minor')->default(0);
            $table->bigInteger('admin_delivery_margin_minor')->default(0);
            $table->unsignedBigInteger('store_net_earnings_minor')->default(0);
            $table->unsignedBigInteger('advance_received_minor')->default(0);
            $table->unsignedBigInteger('remaining_due_minor')->default(0);
            $table->string('settlement_status', 30)->default('settled');
            $table->unsignedBigInteger('settled_by_admin_id')->nullable();
            $table->timestamp('settled_at');
            $table->timestamps();

            $table->index(['booking_id', 'is_current'], 'bfs_booking_current_idx');
        });

        $perms = ['view-admin-accounts', 'adjust-admin-accounts'];
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
        }
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($perms);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_financial_settlements');
        Schema::dropIfExists('admin_account_transactions');
        Schema::dropIfExists('admin_accounts');
        Schema::table('delivery_person_ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_person_ledger_entries', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
        });
        Schema::table('delivery_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_assignments', 'is_manual_rate_override')) {
                $table->dropColumn('is_manual_rate_override');
            }
            if (Schema::hasColumn('delivery_assignments', 'tip_share_minor')) {
                $table->dropColumn('tip_share_minor');
            }
        });
    }
};
