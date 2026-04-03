<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NotificationPreferenceService
{
    /**
     * @return array<int, array{key:string,label:string,description:string,channels:array<int, array{key:string,label:string,enabled:bool,supported:bool}>}>
     */
    public function preferenceMatrixFor(User $user): array
    {
        $channelOrder = $this->availableChannels();
        $storedPreferences = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('notification_key');

        $defaultChannels = $this->defaultEnabledChannels();

        return collect(app(NotificationRegistry::class)->definitions())
            ->map(function (array $definition) use ($channelOrder, $storedPreferences, $defaultChannels): array {
                $notificationKey = $definition['key'];
                $preferenceByChannel = $storedPreferences->get($notificationKey)?->pluck('enabled', 'channel') ?? collect();
                $supportedChannels = collect($definition['supported_channels'])
                    ->map(fn (string $channel): string => $this->normalizeChannel($channel))
                    ->all();
                $allowedChannels = $this->adminAllowedChannels($notificationKey, $supportedChannels);

                return [
                    'key' => $notificationKey,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'channels' => collect($channelOrder)
                        ->map(function (string $channel) use ($preferenceByChannel, $defaultChannels, $allowedChannels): array {
                            $enabled = $preferenceByChannel->has($channel)
                                ? (bool) $preferenceByChannel->get($channel)
                                : in_array($channel, $defaultChannels, true);

                            return [
                                'key' => $channel,
                                'label' => $this->channelLabel($channel),
                                'enabled' => $enabled,
                                'supported' => in_array($channel, $allowedChannels, true),
                            ];
                        })
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, array<string, bool>>  $preferences
     */
    public function syncPreferences(User $user, array $preferences): void
    {
        $definitions = collect(app(NotificationRegistry::class)->definitions())
            ->keyBy('key');
        $registeredKeys = $definitions->keys()->all();
        $allowedChannels = $this->availableChannels();
        $rows = [];
        $now = now();

        foreach ($preferences as $notificationKey => $channels) {
            if (! in_array($notificationKey, $registeredKeys, true) || ! is_array($channels)) {
                continue;
            }

            $supportedChannels = collect($definitions->get($notificationKey, [])['supported_channels'] ?? [])
                ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
                ->map(fn (string $channel): string => $this->normalizeChannel($channel))
                ->all();
            $allowedNotificationChannels = $this->adminAllowedChannels($notificationKey, $supportedChannels);

            foreach ($allowedNotificationChannels as $channel) {
                $rows[] = [
                    'id' => (string) Str::ulid(),
                    'user_id' => $user->id,
                    'notification_key' => $notificationKey,
                    'channel' => $channel,
                    'enabled' => (bool) ($channels[$channel] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows === []) {
            return;
        }

        UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', array_keys($preferences))
            ->delete();

        UserNotificationPreference::query()->insert($rows);
    }

    /**
     * @param  array<int, string>  $supportedChannels
     * @return array<int, string>
     */
    public function resolveChannels(User $user, string $notificationKey, array $supportedChannels): array
    {
        if (! setting_bool('notifications.enabled', true)) {
            return [];
        }

        $normalizedSupportedChannels = collect($supportedChannels)
            ->map(fn (string $channel): string => $this->normalizeChannel($channel))
            ->unique()
            ->values();
        $normalizedSupportedChannels = collect(
            $this->adminAllowedChannels($notificationKey, $normalizedSupportedChannels->all())
        )->values();

        $defaultEnabledChannels = collect($this->defaultEnabledChannels());

        $storedPreferences = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_key', $notificationKey)
            ->whereIn('channel', $normalizedSupportedChannels)
            ->pluck('enabled', 'channel');

        return $normalizedSupportedChannels
            ->filter(function (string $channel) use ($defaultEnabledChannels, $storedPreferences): bool {
                if ($storedPreferences->has($channel)) {
                    return (bool) $storedPreferences->get($channel);
                }

                return $defaultEnabledChannels->contains($channel);
            })
            ->map(fn (string $channel): string => $this->expandChannel($channel))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function defaultEnabledChannels(): array
    {
        return collect(setting_json('notifications.default_channels', ['mail', 'database']))
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->map(fn (string $channel): string => $this->normalizeChannel($channel))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function availableChannels(): array
    {
        return ['database', 'mail', 'sms', 'push'];
    }

    public function channelLabel(string $channel): string
    {
        return Arr::get([
            'database' => 'In-app',
            'mail' => 'Email',
            'sms' => 'SMS',
            'push' => 'Push',
        ], $channel, str($channel)->headline()->value());
    }

    private function normalizeChannel(string $channel): string
    {
        if ($channel === SmsChannel::class) {
            return 'sms';
        }

        return $channel;
    }

    private function expandChannel(string $channel): string
    {
        if ($channel === 'sms') {
            return SmsChannel::class;
        }

        return $channel;
    }

    /**
     * @param  array<int, string>  $fallbackChannels
     * @return array<int, string>
     */
    private function adminAllowedChannels(string $notificationKey, array $fallbackChannels): array
    {
        $normalizedFallback = collect($fallbackChannels)
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->map(fn (string $channel): string => $this->normalizeChannel($channel))
            ->unique()
            ->values();

        $configuredChannels = collect(setting_json(
            NotificationSettings::allowedChannelsSettingKey($notificationKey),
            $normalizedFallback->all(),
        ))
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->map(fn (string $channel): string => $this->normalizeChannel($channel))
            ->filter(fn (string $channel): bool => in_array($channel, $this->availableChannels(), true))
            ->unique()
            ->values();

        return $normalizedFallback
            ->filter(fn (string $channel): bool => $configuredChannels->contains($channel))
            ->values()
            ->all();
    }
}
