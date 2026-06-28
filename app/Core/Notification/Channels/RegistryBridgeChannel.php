<?php

namespace App\Core\Notification\Channels;

use App\Core\Identity\Models\User;
use App\Core\Notification\Contracts\RegistryNotification;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationPreferenceService;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Psr\Log\LoggerInterface;

final class RegistryBridgeChannel
{
    public function __construct(
        private NotificationDispatcher $dispatcher,
        private NotificationPreferenceService $preferences,
        private NotificationChannelRegistry $channelRegistry,
        private LoggerInterface $logger
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        if (! $notification instanceof RegistryNotification) {
            $this->logger->warning('RegistryBridgeChannel: notification does not implement RegistryNotification', [
                'notification' => $notification::class,
            ]);

            return;
        }

        $message = $notification->toNotificationMessage($notifiable)
            ->withMergedMetadata([
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => (string) $notifiable->getKey(),
                'notification_class' => $notification::class,
            ]);
        $supportedChannels = $this->channelRegistry->all();

        if ($supportedChannels === []) {
            $this->logger->warning('RegistryBridgeChannel: no channels registered for dispatch', [
                'notification_key' => $notification->notificationKey(),
                'notification' => $notification::class,
            ]);

            return;
        }

        $enabledChannels = $this->preferences->resolveChannels(
            $notifiable,
            $notification->notificationKey(),
            $supportedChannels,
        );

        $normalizedChannels = collect($enabledChannels)
            ->map(function (string $channel): string {
                if ($channel === WebPushChannel::class || $channel === 'webpush') {
                    return 'push';
                }

                if (str_ends_with($channel, '\\SmsChannel')) {
                    return 'sms';
                }

                return $channel;
            })
            ->unique()
            ->values()
            ->all();

        $this->dispatcher->dispatch($message, $normalizedChannels);
    }
}
