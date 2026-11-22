<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Protected Backup Storage
    |--------------------------------------------------------------------------
    | Backups are stored on the private local disk outside public/ and
    | public/storage/ so they can never be accessed directly via HTTP.
    */
    'storage_disk' => env('DB_BACKUP_DISK', 'local'),
    'storage_directory' => env('DB_BACKUP_DIRECTORY', 'database-backups'),

    /*
    |--------------------------------------------------------------------------
    | Encryption at Rest
    |--------------------------------------------------------------------------
    | When enabled, backup archives are encrypted at rest using Laravel's
    | application key (never stored inside backups or the Git repository).
    */
    'encrypt_at_rest' => (bool) env('DB_BACKUP_ENCRYPT', true),

    /*
    |--------------------------------------------------------------------------
    | MySQL / MariaDB CLI Binary Paths (XAMPP / Laragon Configurable)
    |--------------------------------------------------------------------------
    | Configure DB_BACKUP_MYSQLDUMP_PATH and DB_BACKUP_MYSQL_PATH in .env when
    | XAMPP or Laragon binaries are not on the system PATH.
    */
    'mysqldump_path' => env('DB_BACKUP_MYSQLDUMP_PATH'),
    'mysql_path' => env('DB_BACKUP_MYSQL_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Restore Safeguards & Verification
    |--------------------------------------------------------------------------
    */
    'reauth_window_seconds' => (int) env('DB_BACKUP_REAUTH_SECONDS', 900),
    'verification_database' => env('DB_BACKUP_VERIFICATION_DATABASE'),
    'max_upload_kb' => (int) env('DB_BACKUP_MAX_UPLOAD_KB', 102400), // 100 MB

    /*
    |--------------------------------------------------------------------------
    | Default Retention Policy
    |--------------------------------------------------------------------------
    */
    'retention_keep_count' => (int) env('DB_BACKUP_KEEP_COUNT', 10),
    'retention_keep_days' => (int) env('DB_BACKUP_KEEP_DAYS', 30),
    'max_storage_mb' => (int) env('DB_BACKUP_MAX_STORAGE_MB', 1024),
];
