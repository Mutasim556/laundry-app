<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_enquiries')) {
            Schema::create('contact_enquiries', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 40)->unique();
                $table->string('name', 140);
                $table->string('email', 160)->index();
                $table->string('phone', 40)->nullable();
                $table->string('subject', 180);
                $table->text('message');
                $table->string('booking_reference', 60)->nullable();
                $table->foreignId('booking_id')
                    ->nullable()
                    ->constrained('bookings', 'id', 'ce_booking_fk')
                    ->nullOnDelete();
                $table->string('status', 30)->default('new')->index();
                $table->text('internal_notes')->nullable();
                $table->foreignId('assigned_admin_id')
                    ->nullable()
                    ->constrained('admins', 'id', 'ce_admin_fk')
                    ->nullOnDelete();
                $table->string('idempotency_key', 80)->nullable()->unique();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->string('email_notification_status', 30)->default('pending');
                $table->string('email_notification_error', 255)->nullable();
                $table->timestamp('submitted_at');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
