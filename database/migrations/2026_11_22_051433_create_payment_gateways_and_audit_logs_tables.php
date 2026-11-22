<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_code', 32)->unique();
            $table->string('display_name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->string('environment', 20)->default('sandbox');
            $table->longText('encrypted_credentials')->nullable();
            $table->json('public_configuration')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('configuration_status', 32)->default('unconfigured');
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 20)->nullable();
            $table->string('last_test_message', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateway_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_code', 32)->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 120)->nullable();
            $table->string('action', 64);
            $table->string('summary', 255);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'paypal_order_id')) {
                    $table->string('paypal_order_id', 128)->nullable()->index()->after('stripe_payment_method_id');
                }
                if (! Schema::hasColumn('bookings', 'paypal_capture_id')) {
                    $table->string('paypal_capture_id', 128)->nullable()->index()->after('paypal_order_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('bookings', 'paypal_order_id')) {
                    $dropCols[] = 'paypal_order_id';
                }
                if (Schema::hasColumn('bookings', 'paypal_capture_id')) {
                    $dropCols[] = 'paypal_capture_id';
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        Schema::dropIfExists('payment_gateway_audit_logs');
        Schema::dropIfExists('payment_gateways');
    }
};
