<?php

namespace App\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class AppSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            ...self::applicationSettings(),
            ...self::sessionSettings(),
            ...self::systemSettings(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function applicationSettings(): array
    {
        return [
            [
                'key' => 'app.name',
                'value' => self::value('application.name'),
                'display_name' => 'Application Name',
                'description' => 'The name of your application',
                'type' => 'text',
                'group' => 'app',
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.url',
                'value' => self::value('application.url'),
                'display_name' => 'Application URL',
                'description' => 'The URL of your application',
                'type' => 'url',
                'group' => 'app',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.timezone',
                'value' => self::value('application.timezone'),
                'display_name' => 'Timezone',
                'description' => 'Application timezone for date/time operations',
                'type' => 'select',
                'group' => 'app',
                'options' => self::timezoneOptions(),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.debug',
                'value' => self::value('application.debug'),
                'display_name' => 'Debug Mode',
                'description' => 'Enable debug mode for development',
                'type' => 'select',
                'group' => 'app',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function sessionSettings(): array
    {
        return [
            [
                'key' => 'session.driver',
                'value' => self::value('session.driver'),
                'display_name' => 'Session Driver',
                'description' => 'Session storage driver (file, database, redis, etc.)',
                'type' => 'select',
                'group' => 'session',
                'options' => ['file' => 'File', 'database' => 'Database', 'redis' => 'Redis', 'memcached' => 'Memcached', 'array' => 'Array (Testing)'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.lifetime',
                'value' => self::value('session.lifetime'),
                'display_name' => 'Session Lifetime (minutes)',
                'description' => 'Minutes before session expires',
                'type' => 'number',
                'group' => 'session',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.expire_on_close',
                'value' => self::value('session.expire_on_close'),
                'display_name' => 'Expire On Browser Close',
                'description' => 'Expire session when browser closes',
                'type' => 'select',
                'group' => 'session',
                'options' => ['true' => 'Yes', 'false' => 'No'],
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.encrypt',
                'value' => self::value('session.encrypt'),
                'display_name' => 'Encrypt Session Data',
                'description' => 'Encrypt all session data',
                'type' => 'select',
                'group' => 'session',
                'options' => ['true' => 'Yes', 'false' => 'No'],
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function systemSettings(): array
    {
        return [
            [
                'key' => 'system.date_format',
                'value' => self::value('system.date_format'),
                'display_name' => 'Date Format',
                'description' => 'Date format used throughout the system',
                'type' => 'select',
                'group' => 'system',
                'options' => ['Y-m-d' => '2024-01-15', 'm/d/Y' => '01/15/2024', 'd/m/Y' => '15/01/2024', 'M j, Y' => 'Jan 15, 2024'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.time_format',
                'value' => self::value('system.time_format'),
                'display_name' => 'Time Format',
                'description' => 'Time format used throughout the system',
                'type' => 'select',
                'group' => 'system',
                'options' => ['H:i' => '14:30 (24-hour)', 'g:i A' => '2:30 PM (12-hour)'],
                'order' => 2,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.locale',
                'value' => self::value('system.locale'),
                'display_name' => 'Default Language',
                'description' => 'Default language for the application',
                'type' => 'select',
                'group' => 'system',
                'options' => ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French'],
                'order' => 3,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.work_hours_per_day',
                'value' => self::value('system.work_hours_per_day'),
                'display_name' => 'Default Work Hours Per Day',
                'description' => 'Standard number of work hours per day',
                'type' => 'number',
                'group' => 'system',
                'order' => 4,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.log_level',
                'value' => self::value('system.log_level'),
                'display_name' => 'Log Level',
                'description' => 'Logging level for application logs',
                'type' => 'select',
                'group' => 'system',
                'options' => [
                    'debug' => 'Debug (Development only - very verbose)',
                    'info' => 'Info (Log general information)',
                    'notice' => 'Notice (Normal but significant events)',
                    'warning' => 'Warning (Recommended for production)',
                    'error' => 'Error (Production - only errors and above)',
                    'critical' => 'Critical (Only critical issues)',
                ],
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.cache_enabled',
                'value' => self::value('system.cache_enabled'),
                'display_name' => 'Enable Settings Cache',
                'description' => 'Cache settings for improved performance',
                'type' => 'select',
                'group' => 'system',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.cache_ttl',
                'value' => self::value('system.cache_ttl'),
                'display_name' => 'Cache TTL (seconds)',
                'description' => 'How long to cache settings (in seconds)',
                'type' => 'number',
                'group' => 'system',
                'order' => 7,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.log_changes',
                'value' => self::value('system.log_changes'),
                'display_name' => 'Log Settings Changes',
                'description' => 'Log all settings changes for audit trail',
                'type' => 'select',
                'group' => 'system',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 8,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    

    /**
     * @return array<string, string>
     */
    private static function timezoneOptions(): array
    {
        return [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Anchorage' => 'Alaska Time',
            'America/Adak' => 'Hawaii-Aleutian Time',
            'Europe/London' => 'Greenwich Mean Time',
            'Europe/Paris' => 'Central European Time',
            'Europe/Moscow' => 'Moscow Standard Time',
            'Asia/Dubai' => 'Gulf Standard Time',
            'Asia/Kolkata' => 'Indian Standard Time',
            'Asia/Bangkok' => 'Indochina Time',
            'Asia/Shanghai' => 'China Standard Time',
            'Asia/Seoul' => 'Korea Standard Time',
            'Asia/Tokyo' => 'Japan Standard Time',
            'Australia/Sydney' => 'Australian Eastern Time',
            'Pacific/Auckland' => 'New Zealand Standard Time',
        ];
    }

    private static function value(string $path): string
    {
        return (string) config('settings.'.$path, '');
    }
}
