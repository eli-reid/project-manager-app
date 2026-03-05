<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Mode Settings
    |--------------------------------------------------------------------------
    |
    | When SETTINGS_USE_ENV_IN_DEV is set to true, the settings service will
    | bypass the database and read directly from .env file. This is useful
    | for development where you want quick configuration changes without
    | managing database settings.
    |
    */
    
    'dev_mode' => [
        'use_env_file' => env('SETTINGS_USE_ENV_IN_DEV', false),
        'enabled_environments' => ['local', 'development', 'dev', 'testing'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Settings Database Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is specifically for the settings system which uses
    | a separate SQLite database to avoid bootstrap issues with the main
    | database connection. The settings database is always available.
    |
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
        'ttl' => env('SETTINGS_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'settings',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Settings Encryption
    |--------------------------------------------------------------------------
    */
    
    'encryption' => [
        'enabled' => true,
        'key' => env('APP_KEY', ''),
        'sensitive_keys' => [
            'app_key',
            'db_host',
            'db_database', 
            'db_username',
            'db_password',
            'mail_host',
            'mail_username',
            'mail_password',
            'redis_password',
            'pusher_app_secret',
            'aws_secret_access_key',
            'stripe_secret',
            'paypal_secret',
            'cpanel_api_token',
            'weatherapi_key',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Auto-sync from .env
    |--------------------------------------------------------------------------
    */
    
    'auto_sync_env' => env('SETTINGS_AUTO_SYNC_ENV', true),
    
    'env_mappings' => [
        // App settings
        'app_name' => 'APP_NAME',
        'app_env' => 'APP_ENV', 
        'app_debug' => 'APP_DEBUG',
        'app_url' => 'APP_URL',
        'app_key' => 'APP_KEY',

        // Database settings (for main app database)
        'db_connection' => 'DB_CONNECTION',
        'db_host' => 'DB_HOST',
        'db_port' => 'DB_PORT',
        'db_database' => 'DB_DATABASE',
        'db_username' => 'DB_USERNAME',
        'db_password' => 'DB_PASSWORD',

        // Mail settings
        'mail_mailer' => 'MAIL_MAILER',
        'mail_host' => 'MAIL_HOST',
        'mail_port' => 'MAIL_PORT',
        'mail_username' => 'MAIL_USERNAME',
        'mail_password' => 'MAIL_PASSWORD',
        'mail_encryption' => 'MAIL_ENCRYPTION',
        'mail_from_address' => 'MAIL_FROM_ADDRESS',
        'mail_from_name' => 'MAIL_FROM_NAME',

        // Cache settings
        'cache_driver' => 'CACHE_DRIVER',

        // Queue settings
        'queue_connection' => 'QUEUE_CONNECTION',

        // Session settings
        'session_driver' => 'SESSION_DRIVER',
        'session_lifetime' => 'SESSION_LIFETIME',

        // Logging settings
        'log_level' => 'LOG_LEVEL',
        'log_channel' => 'LOG_CHANNEL',

        // Redis settings
        'redis_host' => 'REDIS_HOST',
        'redis_password' => 'REDIS_PASSWORD',
        'redis_port' => 'REDIS_PORT',

        // Document Storage settings
        'document_storage_disk' => 'DOCUMENT_STORAGE_DISK',
        'max_file_size' => 'MAX_FILE_SIZE',
        'allowed_file_types' => 'ALLOWED_FILE_TYPES',
        'enable_file_versioning' => 'ENABLE_FILE_VERSIONING',

        // AbuseIPDB settings
        'abuseipdb.api_key' => 'ABUSEIPDB_API_KEY',
        'abuseipdb.base_url' => 'ABUSEIPDB_BASE_URL',
    ],
];
