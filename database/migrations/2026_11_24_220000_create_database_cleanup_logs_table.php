<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_cleanup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation_uuid', 64)->unique();
            $table->string('preview_id', 64)->index();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->string('module', 60)->index();
            $table->string('cleanup_mode', 40);
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 120)->nullable();
            $table->string('environment', 40);
            $table->string('database_name', 120);
            $table->unsignedBigInteger('backup_id')->nullable();
            $table->string('backup_reference', 160)->nullable();
            $table->unsignedInteger('targeted_bookings_count')->default(0);
            $table->unsignedInteger('blocked_bookings_count')->default(0);
            $table->unsignedInteger('total_deleted_count')->default(0);
            $table->json('deleted_per_table')->nullable();
            $table->json('post_verification')->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_cleanup_logs');
    }
};
