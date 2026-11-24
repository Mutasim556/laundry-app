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
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 190)->unique();
            $table->string('storage_path', 255);
            $table->string('database_name', 120);
            $table->string('db_engine', 60)->nullable();
            $table->string('db_version', 80)->nullable();
            $table->string('db_charset', 60)->nullable();
            $table->string('db_collation', 80)->nullable();
            $table->string('backup_type', 40)->default('full'); // full, pre_restore_safety, uploaded, scheduled
            $table->string('backup_method', 40)->default('mysqldump'); // mysqldump, php_pdo_fallback, upload
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->boolean('is_encrypted')->default(true);
            $table->string('backup_status', 30)->default('pending'); // pending, completed, failed
            $table->string('verification_status', 40)->default('unverified'); // unverified, archive_integrity_verified, restore_tested, failed
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('source_environment', 60)->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->boolean('is_protected')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['backup_status', 'verification_status']);
            $table->index('created_at');
        });

        Schema::create('database_backup_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('operation_type', 50); // create_backup, download_backup, upload_backup, verify_backup, restore_database, delete_backup, scheduled_backup, update_schedule
            $table->foreignId('database_backup_id')->nullable()->constrained('database_backups')->nullOnDelete();
            $table->foreignId('safety_backup_id')->nullable()->constrained('database_backups')->nullOnDelete();
            $table->string('source_database', 120)->nullable();
            $table->string('target_database', 120)->nullable();
            $table->string('operation_status', 40)->default('pending'); // pending, validating, creating_safety_backup, restoring, verifying, completed, failed
            $table->text('verification_result')->nullable();
            $table->text('failure_summary')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['operation_type', 'operation_status']);
            $table->index('created_at');
        });

        $permissions = [
            'view-database-backups',
            'create-database-backups',
            'download-database-backups',
            'delete-database-backups',
            'restore-database-backups',
            'manage-database-backup-schedules',
        ];

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
        Schema::dropIfExists('database_backup_operations');
        Schema::dropIfExists('database_backups');
    }
};
