<?php

namespace App\Domains\PushNotification\Settings;


use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;
 
class PushNotificationSettings implements SettingsRegistryContract
{
    public const GROUP = 'notifications';

    public static function definitions(): array
    {
        return [
            new Setting(
                key: 'notifications.push.enabled',
                type: SettingType::BOOLEAN,
                formFieldType: SettingFormFieldType::TOGGLE,
                value: 'true',
                options: ['true' => 'Enabled', 'false' => 'Disabled'],
                display_name: 'Enable Push Notifications',
                description: 'Master switch for push notification delivery.',
                group: self::GROUP,
                order: 2,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false
            ),
        ];
    }
}