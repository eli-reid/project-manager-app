<?php

declare(strict_types=1);

namespace App\Settings;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;

class AppSettings implements SettingsRegistryContract
{
    public const GROUP = 'app';

    public static function definitions(): array
    {
        return [
            new Setting(
                key: 'app.name',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('APP_NAME', 'Laravel'),
                display_name: 'Application Name',
                description: 'The public name of the application.',
                group: self::GROUP,
                order: 1,
                is_visible: true,
                is_public: true,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'app.url',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('APP_URL', 'http://localhost'),
                display_name: 'Application URL',
                description: 'Base URL used by the application for generated links.',
                group: self::GROUP,
                order: 2,
                is_visible: true,
                is_public: true,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'app.timezone',
                type: SettingType::ARRAY,
                formFieldType: SettingFormFieldType::SELECT,
                value: env('APP_TIMEZONE', 'UTC'),
                display_name: 'Timezone',
                description: 'Default timezone for the application.',
                group: self::GROUP,
                options: array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
                order: 3,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'app.locale',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('APP_LOCALE', 'en'),
                display_name: 'Default Locale',
                description: 'Default language/locale for the application.',
                group: self::GROUP,
                order: 4,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'app.debug',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: env('APP_DEBUG', false),
                display_name: 'Debug Mode',
                description: 'Enable debug mode (show detailed errors).',
                group: self::GROUP,
                order: 5,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false
            ),
            new Setting(
                key: 'app.maintenance_driver',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::SELECT,
                value: env('APP_MAINTENANCE_DRIVER', 'file'),
                display_name: 'Maintenance Driver',
                description: 'Driver for maintenance mode (file or cache).',
                group: self::GROUP,
                options: ['file' => 'File', 'cache' => 'Cache'],
                order: 6,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'app.support_email',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('APP_SUPPORT_EMAIL', ''),
                display_name: 'Support Email',
                description: 'Email address used for support and system notifications.',
                group: self::GROUP,
                order: 7,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false
            ),
            new Setting(
                key: 'app.enable_registration',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: env('ENABLE_REGISTRATION', true),
                display_name: 'Enable User Registration',
                description: 'Allow users to register for new accounts.',
                group: self::GROUP,
                order: 8,
                is_visible: true,
                is_public: true,
                is_required: false,
                encrypted: false
            ),
        ];
    }
}
