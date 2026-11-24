<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Explicit Environment Enable Switch
    |--------------------------------------------------------------------------
    | Disabled by default. Set DB_CLEANUP_ENABLED=true only in local
    | development/test environments (XAMPP / Laragon). Never enable in production.
    */
    'enabled' => (bool) env('DB_CLEANUP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Approved Environments
    |--------------------------------------------------------------------------
    */
    'allowed_environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Confirmation & Preview Safeguards
    |--------------------------------------------------------------------------
    */
    'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
    'preview_ttl_seconds' => 600,
    'require_verified_backup' => true,
];
