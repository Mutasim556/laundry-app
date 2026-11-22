<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('actor_type', 32)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 120)->nullable();
            $table->string('action', 64);
            $table->string('summary', 255);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'booking_status')) {
                    $table->string('booking_status', 40)->default('confirmed')->index()->after('payment_status');
                }
                if (! Schema::hasColumn('bookings', 'is_locked')) {
                    $table->boolean('is_locked')->default(true)->index()->after('booking_status');
                }
                if (! Schema::hasColumn('bookings', 'idempotency_key')) {
                    $table->string('idempotency_key', 64)->nullable()->unique()->after('reference');
                }
                if (! Schema::hasColumn('bookings', 'contact_snapshot')) {
                    $table->json('contact_snapshot')->nullable()->after('services_snapshot');
                }
                if (! Schema::hasColumn('bookings', 'payment_snapshot')) {
                    $table->json('payment_snapshot')->nullable()->after('fee_snapshot');
                }
                if (! Schema::hasColumn('bookings', 'actual_weight_kg')) {
                    $table->decimal('actual_weight_kg', 8, 2)->nullable()->after('final_amount_minor');
                }
                if (! Schema::hasColumn('bookings', 'item_quantities')) {
                    $table->json('item_quantities')->nullable()->after('actual_weight_kg');
                }
                if (! Schema::hasColumn('bookings', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
                }
                if (! Schema::hasColumn('bookings', 'cancellation_reason')) {
                    $table->string('cancellation_reason', 255)->nullable()->after('cancelled_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $dropCols = [];
                foreach ([
                    'booking_status',
                    'is_locked',
                    'idempotency_key',
                    'contact_snapshot',
                    'payment_snapshot',
                    'actual_weight_kg',
                    'item_quantities',
                    'cancelled_at',
                    'cancellation_reason',
                ] as $col) {
                    if (Schema::hasColumn('bookings', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        Schema::dropIfExists('booking_audit_logs');
        Schema::dropIfExists('booking_sequences');
    }
};
