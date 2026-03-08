<?php

return [
    'application' => [
        'name' => env('APP_NAME', 'Project Manager'),
        'url' => env('APP_URL', 'http://localhost'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'debug' => env('APP_DEBUG', false) ? 'true' : 'false',
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => (string) env('SESSION_LIFETIME', '120'),
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false) ? 'true' : 'false',
        'encrypt' => env('SESSION_ENCRYPT', false) ? 'true' : 'false',
    ],

    'system' => [
        'log_level' => env('LOG_LEVEL', 'error'),
        'cache_enabled' => env('SETTINGS_CACHE_ENABLED', true) ? 'true' : 'false',
        'cache_ttl' => (string) env('SETTINGS_CACHE_TTL', 3600),
        'log_changes' => env('SETTINGS_LOG_CHANGES', false) ? 'true' : 'false',
    ],

    'security' => [
        'session_timeout' => '120',
        'password_min_length' => '8',
        'require_password_complexity' => 'true',
    ],
];
