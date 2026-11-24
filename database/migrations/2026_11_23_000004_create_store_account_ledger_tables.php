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
        Schema::create('store_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('currency', 10)->default('GBP');
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->decimal('total_credited', 14, 2)->default(0.00);
            $table->decimal('total_debited', 14, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['store_id', 'currency'], 'store_accounts_store_currency_unique');
        });

        Schema::create('store_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('booking_store_earning_id')->nullable()->constrained('booking_store_earnings')->nullOnDelete();
            $table->enum('transaction_type', [
                'booking_earning',
                'payout',
                'positive_adjustment',
                'negative_adjustment',
                'reversal',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('GBP');
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->string('reference', 60)->unique();
            $table->string('idempotency_key', 140)->unique();
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->string('description', 500);
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at'], 'st_store_created_idx');
            $table->index('booking_id', 'st_booking_idx');
            $table->index(['store_id', 'currency', 'transaction_type'], 'st_store_curr_type_idx');
        });

        $permissions = ['view-store-accounts', 'adjust-store-accounts'];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
        }
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_transactions');
        Schema::dropIfExists('store_accounts');
    }
};
