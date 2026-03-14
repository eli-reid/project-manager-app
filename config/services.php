<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cpanel' => [
        'url' => env('CPANEL_URL'),
        'username' => env('CPANEL_USERNAME'),
        'api_token' => env('CPANEL_API_TOKEN'),
        'domain' => env('CPANEL_DOMAIN'),
        'port' => env('CPANEL_PORT', 2083),
        'webmail_port' => env('CPANEL_WEBMAIL_PORT', 2096),
        'webmail_url' => env('CPANEL_WEBMAIL_URL'),
        'default_email_quota' => env('CPANEL_DEFAULT_EMAIL_QUOTA', 250),
        'auto_create_emails' => env('CPANEL_AUTO_CREATE_EMAILS', false),
        'auto_delete_emails' => env('CPANEL_AUTO_DELETE_EMAILS', true),
        'sync_user_passwords' => env('CPANEL_SYNC_USER_PASSWORDS', false),
        'queue_write_operations' => env('CPANEL_QUEUE_WRITE_OPERATIONS', false),
        'idempotency_ttl_seconds' => env('CPANEL_IDEMPOTENCY_TTL_SECONDS', 120),
        'queue_tries' => env('CPANEL_QUEUE_TRIES', 3),
        'queue_backoff' => env('CPANEL_QUEUE_BACKOFF', '10,30,60'),
        'failure_threshold' => env('CPANEL_FAILURE_THRESHOLD', 5),
        'cooldown_seconds' => env('CPANEL_COOLDOWN_SECONDS', 300),
        'telemetry_key_prefix' => env('CPANEL_TELEMETRY_KEY_PREFIX', 'cpanel.telemetry'),
        'queue_name' => env('CPANEL_QUEUE_NAME', 'default'),
        'verify_ssl' => env('CPANEL_VERIFY_SSL', true),
        'timeout' => env('CPANEL_TIMEOUT', 30),
        'connect_timeout' => env('CPANEL_CONNECT_TIMEOUT', 10),
    ],

    'weatherapi' => [
        'key' => env('WEATHERAPI_KEY'),
        'base_url' => env('WEATHERAPI_BASE_URL', 'https://api.weatherapi.com/v1'),
        'cache_duration' => (int) env('WEATHERAPI_CACHE_DURATION', 60),
        'timeout' => (int) env('WEATHERAPI_TIMEOUT', 10),
    ],

];
