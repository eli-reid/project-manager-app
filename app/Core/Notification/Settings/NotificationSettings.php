<?php

declare(strict_types=1);

namespace App\Core\Notification\Settings;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;

class NotificationSettings implements SettingsRegistryContract
{
    public const GROUP = 'notifications';

    public static function allowedChannelsSettingKey(string $notificationKey): string
    {
        $normalizedNotificationKey = str($notificationKey)
            ->lower()
            ->replace(['\\', '/', ' '], '.')
            ->replaceMatches('/[^a-z0-9._-]/', '')
            ->trim('.')
            ->value();

        return 'notifications.allowed_channels'.($normalizedNotificationKey !== '' ? '.'.$normalizedNotificationKey : '.');
    }

    public static function definitions(): array
    {
        return [
            new Setting(
                key: 'notifications.enabled',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: 'true',
                display_name: 'Enable Notifications',
                description: 'Master switch for notification delivery.',
                group: self::GROUP,
                options: ['true' => 'Enabled', 'false' => 'Disabled'],
                order: 1,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),

            new Setting(
                key: 'notifications.email.enabled',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: 'true',
                options: ['true' => 'Enabled', 'false' => 'Disabled'],
                display_name: 'Enable Email Notifications',
                description: 'Master switch for email notification delivery.',
                group: self::GROUP,
                order: 2,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'notifications.push.enabled',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: 'true',
                options: ['true' => 'Enabled', 'false' => 'Disabled'],
                display_name: 'Enable Push Notifications',
                description: 'Master switch for push notification delivery (webpush/mobile).',
                group: self::GROUP,
                order: 3,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false
            ),
            new Setting(
                key: 'notifications.default_priority',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::SELECT,
                value: 'normal',
                display_name: 'Default Notification Priority',
                description: 'Default priority for notifications (low, normal, high).',
                group: self::GROUP,
                options: ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'],
                order: 3,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
            new Setting(
                key: 'notifications.default_channels',
                type: SettingType::ARRAY,
                formFieldType: SettingFormFieldType::MULTISELECT,
                value: json_encode(['mail', 'database']),
                display_name: 'Default Notification Channels',
                description: 'Default delivery channels used when a user has not set preferences.',
                group: self::GROUP,
                options: ['mail' => 'Email', 'database' => 'In-app', 'push' => 'Push', 'sms' => 'SMS'],
                order: 4,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
        ];
    }
}
