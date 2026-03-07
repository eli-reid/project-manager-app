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
            ...self::securitySettings(),
            ...self::featureSettings(),
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
                'value' => self::value('application.name', 'Project Manager'),
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
                'value' => self::value('application.url', 'http://localhost'),
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
                'value' => self::value('application.timezone', 'UTC'),
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
                'value' => self::value('application.debug', 'false'),
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
                'value' => self::value('session.driver', 'file'),
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
                'value' => self::value('session.lifetime', '120'),
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
                'value' => self::value('session.expire_on_close', 'false'),
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
                'value' => self::value('session.encrypt', 'false'),
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
                'value' => 'Y-m-d',
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
                'value' => 'H:i',
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
                'value' => 'en',
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
                'value' => '8',
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
                'value' => self::value('system.log_level', 'error'),
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
                'value' => self::value('system.cache_enabled', 'true'),
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
                'value' => self::value('system.cache_ttl', '3600'),
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
                'value' => self::value('system.log_changes', 'false'),
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
     * @return array<int, array<string, mixed>>
     */
    private static function securitySettings(): array
    {
        return [
            [
                'key' => 'security.session_timeout',
                'value' => self::value('security.session_timeout', '120'),
                'display_name' => 'Session Timeout (minutes)',
                'description' => 'Minutes of inactivity before session expires',
                'type' => 'number',
                'group' => 'security',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'security.password_min_length',
                'value' => self::value('security.password_min_length', '8'),
                'display_name' => 'Minimum Password Length',
                'description' => 'Minimum required password length',
                'type' => 'number',
                'group' => 'security',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'security.require_password_complexity',
                'value' => self::value('security.require_password_complexity', 'true'),
                'display_name' => 'Require Password Complexity',
                'description' => 'Require passwords to include uppercase, lowercase, numbers, and symbols',
                'type' => 'select',
                'group' => 'security',
                'options' => ['true' => 'Yes', 'false' => 'No'],
                'order' => 3,
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
    private static function featureSettings(): array
    {
        return [
            [
                'key' => 'features.maintenance_mode',
                'value' => self::value('features.maintenance_mode', 'false'),
                'display_name' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to take application offline',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.new_user_registration',
                'value' => self::value('features.new_user_registration', 'true'),
                'display_name' => 'Allow New User Registration',
                'description' => 'Allow new users to register accounts',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.email_verification',
                'value' => self::value('features.email_verification', 'true'),
                'display_name' => 'Require Email Verification',
                'description' => 'Require users to verify their email before accessing the app',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.notifications',
                'value' => self::value('features.notifications', 'true'),
                'display_name' => 'Enable Notifications',
                'description' => 'Enable system notifications',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.time_tracking',
                'value' => self::value('features.time_tracking', 'true'),
                'display_name' => 'Enable Time Tracking',
                'description' => 'Enable time tracking features',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.reporting',
                'value' => self::value('features.reporting', 'true'),
                'display_name' => 'Enable Reporting',
                'description' => 'Enable reporting features',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 6,
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

    private static function value(string $path, string $default): string
    {
        return (string) config('app-settings.'.$path, $default);
    }
}
