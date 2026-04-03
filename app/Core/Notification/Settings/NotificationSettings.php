<?php

namespace App\Core\Notification\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;
use App\Domains\Projects\Notifications\ProjectNotificationDefinitions;
use App\Domains\Tasks\Notifications\TaskNotificationDefinitions;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;

class NotificationSettings implements DomainSettingsProvider
{
    public const GROUP = 'notifications';

    public static function settings(): array
    {
        return [
            [
                'key' => 'notifications.enabled',
                'value' => 'true',
                'display_name' => 'Enable Notifications',
                'description' => 'Master switch for notification delivery.',
                'type' => 'select',
                'group' => self::GROUP,
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
                'description' => 'JSON array of enabled default channels (database, mail, sms, push).',
                'type' => 'text',
                'group' => self::GROUP,
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            ...self::notificationChannelSettings(),
        ];
    }

    public static function allowedChannelsSettingKey(string $notificationKey): string
    {
        return 'notifications.allowed_channels.'.$notificationKey;
    }

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    private static function notificationDefinitions(): array
    {
        return [
            ...TimecardNotificationDefinitions::definitions(),
            ...ProjectNotificationDefinitions::definitions(),
            ...TaskNotificationDefinitions::definitions(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function notificationChannelSettings(): array
    {
        return collect(self::notificationDefinitions())
            ->values()
            ->map(function (array $definition, int $index): array {
                $channels = collect($definition['supported_channels'] ?? [])
                    ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
                    ->unique()
                    ->values()
                    ->all();

                $label = (string) ($definition['label'] ?? str((string) $definition['key'])->replace(['.', '-', '_'], ' ')->headline()->value());
                $channelList = implode(', ', $channels);

                return [
                    'key' => self::allowedChannelsSettingKey((string) $definition['key']),
                    'value' => json_encode($channels, JSON_THROW_ON_ERROR),
                    'display_name' => $label.' Allowed Channels',
                    'description' => 'Admin-controlled allowed delivery channels for this notification. Use a JSON array with any of: '.$channelList.'. Use [] to disable all delivery channels.',
                    'type' => 'text',
                    'group' => self::GROUP,
                    'order' => 100 + $index,
                    'is_visible' => true,
                    'is_public' => false,
                    'is_required' => true,
                    'encrypted' => false,
                ];
            })
            ->all();
    }
}
