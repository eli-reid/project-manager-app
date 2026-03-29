<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\User\Models\User;

class NotificationPreferenceService
{
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

        $defaultEnabledChannels = collect(setting_json('notifications.default_channels', ['mail', 'database']))
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->map(fn (string $channel): string => $this->normalizeChannel($channel))
            ->values();

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
}
