<?php

namespace Modules\Admin\App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\App\Models\DatabaseBackup;
use Modules\Admin\App\Models\DatabaseBackupOperation;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public function diskName(): string
    {
        return (string) config('database_backups.storage_disk', 'local');
    }

    public function storageDirectory(): string
    {
        return trim((string) config('database_backups.storage_directory', 'database-backups'), '/');
    }

    public function getEnvironmentCapabilities(): array
    {
        $connName = config('database.default');
        $conn = config("database.connections.{$connName}", []);
        $driver = (string) ($conn['driver'] ?? 'mysql');
        $dbName = (string) ($conn['database'] ?? '');

        $version = null;
        $engine = strtoupper($driver);
        $charset = $conn['charset'] ?? null;
        $collation = $conn['collation'] ?? null;
        $reachable = false;

        try {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $row = DB::selectOne('SELECT VERSION() as v');
                $version = $row?->v ? (string) $row->v : null;
                $engine = $version && stripos($version, 'mariadb') !== false ? 'MariaDB' : 'MySQL';
                $csRow = DB::selectOne('SELECT @@character_set_database as cs, @@collation_database as col');
                $charset = $csRow?->cs ?? $charset;
                $collation = $csRow?->col ?? $collation;
            } elseif ($driver === 'sqlite') {
                $row = DB::selectOne('SELECT sqlite_version() as v');
                $version = $row?->v ? (string) $row->v : null;
                $engine = 'SQLite';
            }
            $reachable = true;
        } catch (Throwable) {
            $reachable = false;
        }

        return [
            'connection' => $connName,
            'driver' => $driver,
            'database' => $dbName,
            'engine' => $engine,
            'version' => $version,
            'charset' => $charset,
            'collation' => $collation,
            'reachable' => $reachable,
            'mysqldump_path' => $this->resolveBinary('mysqldump'),
            'mysql_path' => $this->resolveBinary('mysql'),
            'encrypt_at_rest' => (bool) config('database_backups.encrypt_at_rest', true),
        ];
    }

    public function resolveBinary(string $name): ?string
    {
        $configured = config("database_backups.{$name}_path");
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $exe = PHP_OS_FAMILY === 'Windows' ? "{$name}.exe" : $name;

        // Check system PATH via Symfony Process
        $finder = PHP_OS_FAMILY === 'Windows' ? ['where', $exe] : ['which', $name];
        try {
            $proc = new Process($finder);
            $proc->setTimeout(5);
            $proc->run();
            if ($proc->isSuccessful()) {
                $firstLine = trim(explode("\n", trim($proc->getOutput()))[0] ?? '');
                if ($firstLine !== '' && is_file($firstLine)) {
                    return $firstLine;
                }
            }
        } catch (Throwable) {
            // Fall through to common local tool paths
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $patterns = [
                "D:\\laragon\\bin\\mysql\\*\\bin\\{$exe}",
                "C:\\laragon\\bin\\mysql\\*\\bin\\{$exe}",
                "C:\\xampp\\mysql\\bin\\{$exe}",
                "D:\\xampp\\mysql\\bin\\{$exe}",
            ];
            foreach ($patterns as $pattern) {
                $matches = glob($pattern) ?: [];
                if (! empty($matches)) {
                    rsort($matches);
                    if (is_file($matches[0])) {
                        return $matches[0];
                    }
                }
            }
        }

        return null;
    }

    public function createBackup(
        ?int $adminId = null,
        string $backupType = 'full',
        ?string $ip = null,
        ?string $userAgent = null,
        bool $isProtected = false
    ): DatabaseBackup {
        $caps = $this->getEnvironmentCapabilities();
        if (! $caps['reachable']) {
            throw new RuntimeException('Configured database connection is not reachable.');
        }

        $dbName = $caps['database'] ?: 'database';
        $safeDbSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', basename($dbName)) ?: 'db';
        $prefix = $backupType === 'pre_restore_safety' ? 'pre_restore_' : '';
        $timestamp = now()->format('Y-m-d_His');
        $uid = strtolower(Str::random(6));
        $logicalFilename = "{$prefix}{$safeDbSlug}_{$timestamp}_{$uid}.sql.gz";

        $encrypt = (bool) config('database_backups.encrypt_at_rest', true);
        $diskFilename = $encrypt ? "{$logicalFilename}.enc" : $logicalFilename;
        $storagePath = $this->storageDirectory() . '/' . $diskFilename;

        $op = DatabaseBackupOperation::create([
            'admin_id' => $adminId,
            'operation_type' => $backupType === 'scheduled' ? 'scheduled_backup' : 'create_backup',
            'source_database' => $dbName,
            'target_database' => $dbName,
            'operation_status' => 'pending',
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
            'started_at' => now(),
        ]);

        $backup = DatabaseBackup::create([
            'filename' => $logicalFilename,
            'storage_path' => $storagePath,
            'database_name' => $dbName,
            'db_engine' => $caps['engine'],
            'db_version' => $caps['version'],
            'db_charset' => $caps['charset'],
            'db_collation' => $caps['collation'],
            'backup_type' => $backupType,
            'backup_method' => 'pending',
            'file_size_bytes' => 0,
            'is_encrypted' => $encrypt,
            'backup_status' => 'pending',
            'verification_status' => 'unverified',
            'source_environment' => app()->environment(),
            'created_by_admin_id' => $adminId,
            'is_protected' => $isProtected,
        ]);

        $op->update(['database_backup_id' => $backup->id]);

        try {
            $method = 'mysqldump';
            $sqlContent = '';

            if (in_array($caps['driver'], ['mysql', 'mariadb'], true) && $caps['mysqldump_path']) {
                $sqlContent = $this->dumpViaMysqldump($caps['mysqldump_path'], $caps['connection']);
            } else {
                $method = 'php_pdo_fallback';
                $sqlContent = $this->dumpViaPdo($caps['connection'], $caps['driver']);
            }

            if (trim($sqlContent) === '') {
                throw new RuntimeException('Generated SQL dump is empty.');
            }

            $gzipped = gzencode($sqlContent, 6);
            if ($gzipped === false || $gzipped === '') {
                throw new RuntimeException('Failed to compress SQL dump with gzip.');
            }

            $payloadToStore = $encrypt ? Crypt::encryptString($gzipped) : $gzipped;
            $disk = Storage::disk($this->diskName());
            $disk->makeDirectory($this->storageDirectory());
            $disk->put($storagePath, $payloadToStore);

            if (! $disk->exists($storagePath) || $disk->size($storagePath) <= 0) {
                throw new RuntimeException('Backup file failed to persist to protected storage.');
            }

            $checksum = hash('sha256', $payloadToStore);
            $backup->update([
                'backup_method' => $method,
                'file_size_bytes' => strlen($payloadToStore),
                'checksum_sha256' => $checksum,
                'backup_status' => 'completed',
            ]);

            $this->verifyBackup($backup, $adminId, $ip, $userAgent, false);

            $op->update([
                'operation_status' => 'completed',
                'verification_result' => $backup->fresh()->verification_status,
                'completed_at' => now(),
            ]);

            return $backup->fresh();
        } catch (Throwable $e) {
            $backup->update([
                'backup_status' => 'failed',
                'verification_status' => 'failed',
                'verification_notes' => $e->getMessage(),
            ]);
            $op->update([
                'operation_status' => 'failed',
                'failure_summary' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function storeUploadedBackup(
        UploadedFile $file,
        ?int $adminId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): DatabaseBackup {
        $origName = $file->getClientOriginalName();
        $lower = strtolower($origName);
        if (! str_ends_with($lower, '.sql') && ! str_ends_with($lower, '.sql.gz')) {
            throw new RuntimeException('Only .sql and .sql.gz backup files are supported.');
        }

        $rawBytes = file_get_contents($file->getRealPath());
        if ($rawBytes === false || $rawBytes === '') {
            throw new RuntimeException('Uploaded backup file is empty or unreadable.');
        }

        $sqlContent = str_ends_with($lower, '.sql.gz') ? @gzdecode($rawBytes) : $rawBytes;
        if ($sqlContent === false || trim($sqlContent) === '') {
            throw new RuntimeException('Uploaded .sql.gz archive is corrupted or empty.');
        }

        $this->assertSafeSqlContent($sqlContent);

        $caps = $this->getEnvironmentCapabilities();
        $timestamp = now()->format('Y-m-d_His');
        $uid = strtolower(Str::random(6));
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo(str_replace('.sql.gz', '', $origName), PATHINFO_FILENAME)) ?: 'uploaded';
        $logicalFilename = "uploaded_{$base}_{$timestamp}_{$uid}.sql.gz";

        $encrypt = (bool) config('database_backups.encrypt_at_rest', true);
        $diskFilename = $encrypt ? "{$logicalFilename}.enc" : $logicalFilename;
        $storagePath = $this->storageDirectory() . '/' . $diskFilename;

        $gzipped = gzencode($sqlContent, 6);
        $payload = $encrypt ? Crypt::encryptString($gzipped) : $gzipped;

        $disk = Storage::disk($this->diskName());
        $disk->makeDirectory($this->storageDirectory());
        $disk->put($storagePath, $payload);

        $backup = DatabaseBackup::create([
            'filename' => $logicalFilename,
            'storage_path' => $storagePath,
            'database_name' => $this->extractDatabaseNameFromSql($sqlContent) ?? ($caps['database'] ?: 'uploaded'),
            'db_engine' => $this->extractEngineFromSql($sqlContent),
            'db_version' => $this->extractVersionFromSql($sqlContent),
            'backup_type' => 'uploaded',
            'backup_method' => 'upload',
            'file_size_bytes' => strlen($payload),
            'checksum_sha256' => hash('sha256', $payload),
            'is_encrypted' => $encrypt,
            'backup_status' => 'completed',
            'verification_status' => 'unverified',
            'source_environment' => 'uploaded',
            'created_by_admin_id' => $adminId,
        ]);

        DatabaseBackupOperation::create([
            'admin_id' => $adminId,
            'operation_type' => 'upload_backup',
            'database_backup_id' => $backup->id,
            'source_database' => $backup->database_name,
            'target_database' => $caps['database'],
            'operation_status' => 'completed',
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return $this->verifyBackup($backup, $adminId, $ip, $userAgent, true);
    }

    public function getDecryptedArchiveBytes(DatabaseBackup $backup): string
    {
        $disk = Storage::disk($this->diskName());
        if (! str_starts_with($backup->storage_path, $this->storageDirectory() . '/') || str_contains($backup->storage_path, '..')) {
            throw new RuntimeException('Invalid managed backup storage path.');
        }
        if (! $disk->exists($backup->storage_path)) {
            throw new RuntimeException('Backup file no longer exists in protected storage.');
        }

        $stored = $disk->get($backup->storage_path);
        if ($backup->checksum_sha256 && ! hash_equals($backup->checksum_sha256, hash('sha256', $stored))) {
            throw new RuntimeException('Backup checksum mismatch detected.');
        }

        return $backup->is_encrypted ? Crypt::decryptString($stored) : $stored;
    }

    public function verifyBackup(
        DatabaseBackup $backup,
        ?int $adminId = null,
        ?string $ip = null,
        ?string $userAgent = null,
        bool $recordOp = true
    ): DatabaseBackup {
        $notes = [];
        try {
            $gzipped = $this->getDecryptedArchiveBytes($backup);
            $sql = @gzdecode($gzipped);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Gzip archive integrity check failed.');
            }

            $this->assertSafeSqlContent($sql);

            $caps = $this->getEnvironmentCapabilities();
            if ($backup->db_engine && $caps['engine'] && strcasecmp($backup->db_engine, $caps['engine']) !== 0) {
                $notes[] = "Compatibility note: Backup engine ({$backup->db_engine}) differs from active engine ({$caps['engine']}).";
            }

            $status = 'archive_integrity_verified';
            $notes[] = 'Checksum, encryption, gzip integrity, and SQL structure verified.';

            $verifyDb = config('database_backups.verification_database');
            if (is_string($verifyDb) && $verifyDb !== '' && $verifyDb !== $caps['database'] && $caps['mysql_path']) {
                $this->importSqlToTargetDatabase($sql, $caps['mysql_path'], $caps['connection'], $verifyDb);
                $status = 'restore_tested';
                $notes[] = "Test-restored cleanly into disposable verification database ({$verifyDb}).";
            }

            $backup->update([
                'verification_status' => $status,
                'verified_at' => now(),
                'verification_notes' => implode(' ', $notes),
            ]);

            if ($recordOp) {
                DatabaseBackupOperation::create([
                    'admin_id' => $adminId,
                    'operation_type' => 'verify_backup',
                    'database_backup_id' => $backup->id,
                    'source_database' => $backup->database_name,
                    'target_database' => $caps['database'],
                    'operation_status' => 'completed',
                    'verification_result' => $status,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);
            }

            return $backup->fresh();
        } catch (Throwable $e) {
            $backup->update([
                'verification_status' => 'failed',
                'verified_at' => now(),
                'verification_notes' => $e->getMessage(),
            ]);

            if ($recordOp) {
                DatabaseBackupOperation::create([
                    'admin_id' => $adminId,
                    'operation_type' => 'verify_backup',
                    'database_backup_id' => $backup->id,
                    'source_database' => $backup->database_name,
                    'operation_status' => 'failed',
                    'failure_summary' => $e->getMessage(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    public function initiateRestore(
        DatabaseBackup $backup,
        int $adminId,
        ?string $ip = null,
        ?string $userAgent = null
    ): DatabaseBackupOperation {
        if ($backup->backup_status !== 'completed') {
            throw new RuntimeException('Selected backup is not in completed state.');
        }

        $activeOp = DatabaseBackupOperation::query()
            ->where('operation_type', 'restore_database')
            ->whereIn('operation_status', ['pending', 'validating', 'creating_safety_backup', 'restoring', 'verifying'])
            ->exists();

        if ($activeOp) {
            throw new RuntimeException('Another database restore operation is already in progress.');
        }

        $caps = $this->getEnvironmentCapabilities();

        return DatabaseBackupOperation::create([
            'admin_id' => $adminId,
            'operation_type' => 'restore_database',
            'database_backup_id' => $backup->id,
            'source_database' => $backup->database_name,
            'target_database' => $caps['database'],
            'operation_status' => 'pending',
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
            'started_at' => now(),
        ]);
    }

    public function executeRestore(DatabaseBackupOperation $operation, bool $useMaintenanceMode = true): DatabaseBackupOperation
    {
        $lock = Cache::lock('database_restore_lock', 600);
        if (! $lock->get()) {
            $operation->update([
                'operation_status' => 'failed',
                'failure_summary' => 'Could not acquire restore lock; another backup or restore operation is active.',
                'completed_at' => now(),
            ]);

            return $operation->fresh();
        }

        $enteredMaintenance = false;

        try {
            $backup = $operation->backup;
            if (! $backup) {
                throw new RuntimeException('Target backup record not found.');
            }

            // Stage 1: Validating
            $operation->update(['operation_status' => 'validating']);
            $this->verifyBackup($backup, $operation->admin_id, $operation->ip_address, $operation->user_agent, false);
            $gzipped = $this->getDecryptedArchiveBytes($backup);
            $sql = @gzdecode($gzipped);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Failed to decode SQL dump for restore.');
            }

            $caps = $this->getEnvironmentCapabilities();
            if (! $caps['reachable']) {
                throw new RuntimeException('Target database is not reachable.');
            }

            // Stage 2: Mandatory Pre-Restore Safety Backup
            $operation->update(['operation_status' => 'creating_safety_backup']);
            $safetyBackup = $this->createBackup(
                $operation->admin_id,
                'pre_restore_safety',
                $operation->ip_address,
                $operation->user_agent,
                true
            );
            $operation->update(['safety_backup_id' => $safetyBackup->id]);
            $safetyBackupSnapshot = $safetyBackup->fresh()->getAttributes();
            $operationSnapshot = $operation->fresh()->getAttributes();

            // Stage 3: Restoring
            $operation->update(['operation_status' => 'restoring']);
            if ($useMaintenanceMode && ! app()->runningUnitTests()) {
                Artisan::call('down', ['--retry' => 15]);
                $enteredMaintenance = true;
            }

            if (in_array($caps['driver'], ['mysql', 'mariadb'], true) && $caps['mysql_path']) {
                $this->importSqlToTargetDatabase($sql, $caps['mysql_path'], $caps['connection'], $caps['database']);
            } else {
                $this->importSqlViaPdo($sql, $caps['connection'], $caps['driver']);
            }

            // Re-persist the safety backup & active operation metadata if the restored dump predated them
            $restoredTarget = DatabaseBackup::where('filename', $backup->filename)->first();
            if ($restoredTarget && $restoredTarget->backup_status !== 'completed') {
                $restoredTarget->update([
                    'backup_status' => 'completed',
                    'file_size_bytes' => $backup->file_size_bytes,
                    'checksum_sha256' => $backup->checksum_sha256,
                    'verification_status' => $backup->verification_status,
                    'verified_at' => $backup->verified_at,
                ]);
            }
            if (! DatabaseBackup::where('filename', $safetyBackupSnapshot['filename'])->exists()) {
                unset($safetyBackupSnapshot['id']);
                $restoredSafety = DatabaseBackup::create($safetyBackupSnapshot);
                $operationSnapshot['safety_backup_id'] = $restoredSafety->id;
            }
            $persistedOp = DatabaseBackupOperation::find($operation->id);
            if (! $persistedOp) {
                unset($operationSnapshot['id'], $operationSnapshot['database_backup_id']);
                $persistedOp = DatabaseBackupOperation::create($operationSnapshot);
                $operation = $persistedOp;
            }

            // Stage 4: Verifying restored database health
            $operation->update(['operation_status' => 'verifying']);
            $this->verifyPostRestoreHealth($caps['driver']);

            $operation->update([
                'operation_status' => 'completed',
                'verification_result' => 'Post-restore table and migration checks passed.',
                'completed_at' => now(),
            ]);

            return $operation->fresh();
        } catch (Throwable $e) {
            $persisted = DatabaseBackupOperation::find($operation->id);
            if ($persisted) {
                $persisted->update([
                    'operation_status' => 'failed',
                    'failure_summary' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                return $persisted->fresh();
            }

            return $operation;
        } finally {
            if ($enteredMaintenance) {
                try {
                    Artisan::call('up');
                } catch (Throwable) {
                    // Best-effort recovery from maintenance mode
                }
            }
            $lock->release();
        }
    }

    public function deleteBackup(DatabaseBackup $backup, ?int $adminId = null, ?string $ip = null, ?string $userAgent = null): void
    {
        if ($backup->is_protected) {
            throw new RuntimeException('Protected safety backups cannot be deleted.');
        }

        $isUsedByActiveRestore = DatabaseBackupOperation::query()
            ->where(function ($q) use ($backup) {
                $q->where('database_backup_id', $backup->id)
                    ->orWhere('safety_backup_id', $backup->id);
            })
            ->whereIn('operation_status', ['pending', 'validating', 'creating_safety_backup', 'restoring', 'verifying'])
            ->exists();

        if ($isUsedByActiveRestore) {
            throw new RuntimeException('Cannot delete a backup currently used by an active restore operation.');
        }

        if (! str_starts_with($backup->storage_path, $this->storageDirectory() . '/') || str_contains($backup->storage_path, '..')) {
            throw new RuntimeException('Managed backup path validation failed.');
        }

        $disk = Storage::disk($this->diskName());
        if ($disk->exists($backup->storage_path)) {
            $disk->delete($backup->storage_path);
        }

        DatabaseBackupOperation::create([
            'admin_id' => $adminId,
            'operation_type' => 'delete_backup',
            'source_database' => $backup->database_name,
            'operation_status' => 'completed',
            'verification_result' => "Deleted backup {$backup->filename}",
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250) : null,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $backup->delete();
    }

    public function pruneOldBackups(int $keepCount, int $keepDays, int $maxStorageMb): int
    {
        $backups = DatabaseBackup::query()
            ->where('backup_status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $verifiedIds = $backups
            ->whereIn('verification_status', ['archive_integrity_verified', 'restore_tested'])
            ->pluck('id')
            ->all();

        $lastVerifiedId = $verifiedIds[0] ?? null;
        $deleted = 0;
        $keptBytes = 0;
        $maxBytes = $maxStorageMb * 1024 * 1024;
        $cutoff = now()->subDays(max(1, $keepDays));

        foreach ($backups as $idx => $backup) {
            $mustKeep = $backup->is_protected || ($lastVerifiedId !== null && $backup->id === $lastVerifiedId);
            $overCount = $idx >= $keepCount;
            $tooOld = $backup->created_at && $backup->created_at->lt($cutoff);
            $overStorage = ($keptBytes + $backup->file_size_bytes) > $maxBytes;

            if (! $mustKeep && ($overCount || $tooOld || $overStorage)) {
                try {
                    $this->deleteBackup($backup);
                    $deleted++;
                    continue;
                } catch (Throwable) {
                    // Skip protected/in-use
                }
            }

            $keptBytes += (int) $backup->file_size_bytes;
        }

        return $deleted;
    }

    protected function dumpViaMysqldump(string $binary, string $connectionName): string
    {
        $conn = config("database.connections.{$connectionName}");
        $cnfPath = $this->createTempDefaultsFile($conn);

        try {
            $args = [
                $binary,
                "--defaults-extra-file={$cnfPath}",
                '--single-transaction',
                '--routines',
                '--triggers',
                '--events',
                '--hex-blob',
                '--skip-lock-tables',
                (string) $conn['database'],
            ];

            $proc = new Process($args);
            $proc->setTimeout(300);
            $proc->run();

            if (! $proc->isSuccessful()) {
                throw new RuntimeException('mysqldump failed: ' . Str::limit(trim($proc->getErrorOutput()), 300));
            }

            return $proc->getOutput();
        } finally {
            @unlink($cnfPath);
        }
    }

    protected function importSqlToTargetDatabase(string $sql, string $mysqlBinary, string $connectionName, string $targetDb): void
    {
        $conn = config("database.connections.{$connectionName}");
        $cnfPath = $this->createTempDefaultsFile($conn);
        $sqlPath = tempnam(sys_get_temp_dir(), 'db_restore_');

        try {
            file_put_contents($sqlPath, $sql);
            $proc = new Process([
                $mysqlBinary,
                "--defaults-extra-file={$cnfPath}",
                '--default-character-set=' . ($conn['charset'] ?? 'utf8mb4'),
                $targetDb,
            ]);
            $proc->setTimeout(600);
            $proc->setInput(fopen($sqlPath, 'rb'));
            $proc->run();

            if (! $proc->isSuccessful()) {
                throw new RuntimeException('mysql import failed: ' . Str::limit(trim($proc->getErrorOutput()), 300));
            }
        } finally {
            @unlink($sqlPath);
            @unlink($cnfPath);
        }
    }

    protected function createTempDefaultsFile(array $conn): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mycnf_');
        $lines = [
            '[client]',
            'host=' . ($conn['host'] ?? '127.0.0.1'),
            'port=' . ($conn['port'] ?? 3306),
            'user=' . ($conn['username'] ?? 'root'),
            'password="' . str_replace('"', '\\"', (string) ($conn['password'] ?? '')) . '"',
        ];
        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
        @chmod($path, 0600);

        return $path;
    }

    protected function dumpViaPdo(string $connectionName, string $driver): string
    {
        $pdo = DB::connection($connectionName)->getPdo();
        $out = "-- Laundry App PHP PDO SQL Backup\n-- Driver: {$driver}\n-- Generated at: " . now()->toIso8601String() . "\n\n";

        if ($driver === 'sqlite') {
            $tables = DB::connection($connectionName)->select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            foreach ($tables as $t) {
                $out .= "DROP TABLE IF EXISTS \"{$t->name}\";\n{$t->sql};\n";
                $rows = DB::connection($connectionName)->table($t->name)->get();
                foreach ($rows as $row) {
                    $arr = (array) $row;
                    $cols = implode(', ', array_map(fn ($c) => "\"{$c}\"", array_keys($arr)));
                    $vals = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($arr)));
                    $out .= "INSERT INTO \"{$t->name}\" ({$cols}) VALUES ({$vals});\n";
                }
                $out .= "\n";
            }

            return $out;
        }

        $tables = DB::connection($connectionName)->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $row) {
            $tableName = array_values((array) $row)[0];
            $create = DB::connection($connectionName)->selectOne("SHOW CREATE TABLE `{$tableName}`");
            $createSql = array_values((array) $create)[1] ?? '';
            $out .= "DROP TABLE IF EXISTS `{$tableName}`;\n{$createSql};\n";
            foreach (DB::connection($connectionName)->table($tableName)->cursor() as $r) {
                $arr = (array) $r;
                $cols = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($arr)));
                $vals = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($arr)));
                $out .= "INSERT INTO `{$tableName}` ({$cols}) VALUES ({$vals});\n";
            }
            $out .= "\n";
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $out;
    }

    protected function importSqlViaPdo(string $sql, string $connectionName, string $driver): void
    {
        $pdo = DB::connection($connectionName)->getPdo();
        if ($driver === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = OFF;');
            $pdo->exec($sql);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            return;
        }
        $pdo->exec($sql);
    }

    protected function verifyPostRestoreHealth(string $driver): void
    {
        $required = ['migrations', 'users', 'admins', 'bookings', 'stores'];
        foreach ($required as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("Post-restore verification failed: required table '{$table}' is missing.");
            }
        }
        DB::table('migrations')->count();
    }

    protected function assertSafeSqlContent(string $sql): void
    {
        if (str_contains($sql, '<?php') || str_contains($sql, '<?=')) {
            throw new RuntimeException('Rejected backup: unexpected PHP code tags detected inside SQL dump.');
        }
        if (! preg_match('/\b(CREATE\s+TABLE|INSERT\s+INTO|SET\s+FOREIGN_KEY_CHECKS|--\s+MySQL|--\s+MariaDB|--\s+Laundry)/i', $sql)) {
            throw new RuntimeException('File does not appear to be a valid SQL database dump.');
        }
    }

    protected function extractDatabaseNameFromSql(string $sql): ?string
    {
        if (preg_match('/--\s+Host:.*?Database:\s*([A-Za-z0-9_\-]+)/i', $sql, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function extractEngineFromSql(string $sql): ?string
    {
        if (stripos($sql, 'MariaDB') !== false) {
            return 'MariaDB';
        }
        if (stripos($sql, 'MySQL') !== false) {
            return 'MySQL';
        }

        return null;
    }

    protected function extractVersionFromSql(string $sql): ?string
    {
        if (preg_match('/Server version\s+([0-9A-Za-z.\-]+)/i', $sql, $m)) {
            return $m[1];
        }

        return null;
    }
}
