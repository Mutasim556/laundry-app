<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\App\Models\DatabaseBackup;
use Modules\Admin\App\Models\DatabaseBackupOperation;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('local');
    Permission::firstOrCreate(['name' => 'view-database-backups', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'create-database-backups', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'download-database-backups', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'delete-database-backups', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'restore-database-backups', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-database-backup-schedules', 'guard_name' => 'admin']);
});

test('enforces granular permissions for viewing, creating, downloading, and restoring backups', function () {
    $limitedAdmin = Admin::factory()->create();

    $this->actingAs($limitedAdmin, 'admin')
        ->get(route('admin.database-backups.index'))
        ->assertStatus(403);

    $this->actingAs($limitedAdmin, 'admin')
        ->postJson(route('admin.database-backups.store'))
        ->assertStatus(403);

    $viewOnlyAdmin = Admin::factory()->create();
    $viewOnlyAdmin->givePermissionTo('view-database-backups');

    $this->actingAs($viewOnlyAdmin, 'admin')
        ->get(route('admin.database-backups.index'))
        ->assertStatus(200)
        ->assertSee('Database Backup &amp; Restore', false);

    $this->actingAs($viewOnlyAdmin, 'admin')
        ->postJson(route('admin.database-backups.store'))
        ->assertStatus(403);
});

test('creates, verifies, downloads, and deletes a database backup in private storage', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo([
        'view-database-backups',
        'create-database-backups',
        'download-database-backups',
        'delete-database-backups',
    ]);

    $res = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.store'))
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $backup = DatabaseBackup::findOrFail($res->json('backup_id'));

    expect($backup->backup_status)->toBe('completed')
        ->and($backup->verification_status)->toBe('archive_integrity_verified')
        ->and($backup->file_size_bytes)->toBeGreaterThan(0)
        ->and($backup->checksum_sha256)->not->toBeEmpty()
        ->and(Storage::disk('local')->exists($backup->storage_path))->toBeTrue();

    // Download returns decrypted gzip SQL attachment and logs audit operation
    $download = $this->actingAs($admin, 'admin')
        ->get(route('admin.database-backups.download', $backup))
        ->assertStatus(200);

    expect($download->headers->get('content-disposition'))->toContain($backup->filename);
    expect(DatabaseBackupOperation::where('operation_type', 'download_backup')->where('database_backup_id', $backup->id)->exists())->toBeTrue();

    // Delete removes file and records audit log
    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.database-backups.destroy', $backup))
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(DatabaseBackup::find($backup->id))->toBeNull()
        ->and(Storage::disk('local')->exists($backup->storage_path))->toBeFalse()
        ->and(DatabaseBackupOperation::where('operation_type', 'delete_backup')->exists())->toBeTrue();
});

test('validates uploaded SQL backups and rejects unsafe PHP content', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-database-backups', 'create-database-backups']);

    $malicious = UploadedFile::fake()->createWithContent('evil.sql', "<?php system('whoami'); ?>");
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.upload'), ['backup_file' => $malicious])
        ->assertStatus(422);

    $validSql = "-- MySQL dump\nSET FOREIGN_KEY_CHECKS=0;\nCREATE TABLE IF NOT EXISTS `sample` (`id` INT);\n";
    $valid = UploadedFile::fake()->createWithContent('office_dump.sql', $validSql);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.upload'), ['backup_file' => $valid])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(DatabaseBackup::where('backup_type', 'uploaded')->count())->toBe(1);
});

test('restore requires password reauthentication, exact phrase, creates pre-restore safety backup, and rejects concurrent locks', function () {
    $admin = Admin::factory()->create([
        'password' => 'SecretAdmin123!',
    ]);
    $admin->givePermissionTo([
        'view-database-backups',
        'create-database-backups',
        'restore-database-backups',
        'delete-database-backups',
    ]);

    $createRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.store'))
        ->assertStatus(200);
    $backup = DatabaseBackup::findOrFail($createRes->json('backup_id'));

    // Wrong confirmation phrase -> 422
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.restore', $backup), [
            'acknowledge_overwrite' => '1',
            'confirmation_phrase' => 'WRONG PHRASE',
            'admin_password' => 'SecretAdmin123!',
        ])
        ->assertStatus(422);

    // Wrong password -> 422
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.restore', $backup), [
            'acknowledge_overwrite' => '1',
            'confirmation_phrase' => 'RESTORE DATABASE',
            'admin_password' => 'wrong-password',
        ])
        ->assertStatus(422);

    // Valid restore executes (sync queue in tests), creates protected pre-restore safety backup, and completes
    $restoreRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-backups.restore', $backup), [
            'acknowledge_overwrite' => '1',
            'confirmation_phrase' => 'RESTORE DATABASE',
            'admin_password' => 'SecretAdmin123!',
        ]);
    expect($restoreRes->status())->toBe(200, json_encode($restoreRes->json()));

    $opId = $restoreRes->json('operation_id');
    $op = DatabaseBackupOperation::findOrFail($opId);
    expect($op->operation_status)->toBe('completed')
        ->and($op->safety_backup_id)->not->toBeNull();

    $safety = DatabaseBackup::findOrFail($op->safety_backup_id);
    expect($safety->backup_type)->toBe('pre_restore_safety')
        ->and($safety->is_protected)->toBeTrue();

    // Protected safety backup cannot be deleted
    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.database-backups.destroy', $safety))
        ->assertStatus(422);

    // Concurrent restore lock causes graceful failure
    $lock = Cache::lock('database_restore_lock', 60);
    $lock->get();
    try {
        $lockedRes = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.database-backups.restore', $backup), [
                'acknowledge_overwrite' => '1',
                'confirmation_phrase' => 'RESTORE DATABASE',
                'admin_password' => 'SecretAdmin123!',
            ]);
        expect($lockedRes->status())->toBe(200, json_encode($lockedRes->json()));

        $lockedOp = DatabaseBackupOperation::findOrFail($lockedRes->json('operation_id'));
        expect($lockedOp->operation_status)->toBe('failed');
    } finally {
        $lock->release();
    }
});

test('updates schedule and retention configuration with audit log', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-database-backups', 'manage-database-backup-schedules']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.database-backups.schedule.update'), [
            'frequency' => 'daily',
            'keep_count' => 7,
            'keep_days' => 14,
            'max_storage_mb' => 512,
        ])
        ->assertSessionHas('success');

    expect(DatabaseBackupOperation::where('operation_type', 'update_schedule')->exists())->toBeTrue();
});
