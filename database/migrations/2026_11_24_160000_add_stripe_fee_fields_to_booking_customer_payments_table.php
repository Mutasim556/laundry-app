<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_customer_payments', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id', 120)->nullable()->after('external_reference');
            $table->string('stripe_charge_id', 120)->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_balance_transaction_id', 120)->nullable()->after('stripe_charge_id');
            $table->unsignedBigInteger('gateway_gross_minor')->nullable()->after('stripe_balance_transaction_id');
            $table->unsignedBigInteger('gateway_fee_minor')->nullable()->after('gateway_gross_minor');
            $table->unsignedBigInteger('gateway_net_minor')->nullable()->after('gateway_fee_minor');
            $table->string('gateway_fee_currency', 8)->nullable()->after('gateway_net_minor');
            $table->json('gateway_fee_details')->nullable()->after('gateway_fee_currency');
            $table->string('fee_reconciliation_status', 32)->nullable()->default('pending')->after('gateway_fee_details');
            $table->timestamp('fee_reconciled_at')->nullable()->after('fee_reconciliation_status');

            $table->index('stripe_payment_intent_id', 'bcp_stripe_pi_idx');
            $table->index('stripe_balance_transaction_id', 'bcp_stripe_bt_idx');
            $table->index('fee_reconciliation_status', 'bcp_fee_rec_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('booking_customer_payments', function (Blueprint $table) {
            $table->dropIndex('bcp_stripe_pi_idx');
            $table->dropIndex('bcp_stripe_bt_idx');
            $table->dropIndex('bcp_fee_rec_status_idx');
            $table->dropColumn([
                'stripe_payment_intent_id',
                'stripe_charge_id',
                'stripe_balance_transaction_id',
                'gateway_gross_minor',
                'gateway_fee_minor',
                'gateway_net_minor',
                'gateway_fee_currency',
                'gateway_fee_details',
                'fee_reconciliation_status',
                'fee_reconciled_at',
            ]);
        });
    }
};
