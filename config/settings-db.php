<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Mode Settings
    |--------------------------------------------------------------------------
    */
    'dev_mode' => [
        'use_env_file' => env('SETTINGS_USE_ENV_IN_DEV', false),
        'enabled_environments' => ['local', 'development', 'dev', 'testing'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Database Configuration
    |--------------------------------------------------------------------------
    */
    'connection' => 'settings_sqlite',
    'database_path' => env('SETTINGS_DB_PATH', base_path('settings.data')),
    'table_name' => 'settings',

    /*
    |--------------------------------------------------------------------------
    | Settings Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'forever' => env('SETTINGS_CACHE_FOREVER', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 3600),
        'prefix' => 'settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Definition Sync
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'on_boot' => env('SETTINGS_SYNC_ON_BOOT'),
        'check_interval_seconds' => env('SETTINGS_SYNC_CHECK_INTERVAL', 300),
        'prune_undefined_on_seed' => env('SETTINGS_PRUNE_UNDEFINED_ON_SEED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Logging
    |--------------------------------------------------------------------------
    */
    'log_changes' => env('SETTINGS_LOG_CHANGES', false),

    /*
    |--------------------------------------------------------------------------
    | Optional Env Mappings (legacy compatibility)
    |--------------------------------------------------------------------------
    */
    'env_mappings' => [],
];
