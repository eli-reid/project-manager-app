<?php

namespace App\Core\Notification\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class NotificationSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            [
                'key' => 'notifications.enabled',
                'value' => 'true',
                'display_name' => 'Enable Notifications',
                'description' => 'Master switch for notification delivery.',
                'type' => 'select',
                'group' => 'notifications',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'notifications.default_channels',
                'value' => '["mail", "database"]',
                'display_name' => 'Default Notification Channels',
                'description' => 'JSON array of enabled default channels (mail, database, sms).',
                'type' => 'text',
                'group' => 'notifications',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
        ];
    }
}
