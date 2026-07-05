<?php

namespace App\Core\Notification\Channels\PushNotification\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use NotificationChannels\WebPush\PushSubscription;

final class PushChannel implements NotificationChannel
{
    public function convert(NotificationMessage $message): ChannelMessage
    {
        return new class(['id' => $message->id, 'title' => $message->title ?? 'Notification', 'body' => $message->body ?? '', 'data' => $message->data, 'recipients' => $message->recipients, 'metadata' => $message->metadata]) extends ChannelMessage
        {
            public function channelName(): string
            {
                return 'push';
            }
        };
    }

    public function send(ChannelMessage $message): ChannelResult
    {
        $recipients = collect($message->recipients ?? []);
        if ($recipients->isEmpty()) {
            return ChannelResult::failure('push', null, ['message' => 'no push recipients']);
        }   

        $recipients = $recipients->map(fn (mixed $recipient): ?PushSubscription => $recipient instanceof PushSubscription ? $recipient : null)
            ->filter(fn (?PushSubscription $subscription): bool => $subscription !== null)
            ->values();
        if ($recipients->isEmpty()) {
            return ChannelResult::failure('push', null, ['message' => 'no valid push recipients']);
        }

        return ChannelResult::success('push', null, [
            'message' => 'sent',
            'recipientStatus' => $recipients->mapWithKeys(fn (PushSubscription $subscription): array => [$subscription->endpoint => ['status' => 'delivered', 'metadata' => []]])->all(),
        ]);
    }
}
