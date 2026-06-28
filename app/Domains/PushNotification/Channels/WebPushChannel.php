<?php

namespace App\Domains\PushNotification\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\DTO\ChannelResult;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

final class WebPushChannel implements NotificationChannel
{
    public function __construct(private ?WebPush $webPush = null)
    {
    }

    public function convert(NotificationMessage $message): ChannelMessage
    {
        $payload = [
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
            'id' => $message->id,
        ];

        return new class($payload) extends ChannelMessage {
            public function channelName(): string { return 'webpush'; }
        };
    }

    public function send(ChannelMessage $message): ChannelResult
    {
        // If Minishlink WebPush is not available, return a failure result
        if (! class_exists(WebPush::class)) {
            return ChannelResult::failure('webpush', null, ['message' => 'web-push library not installed']);
        }

        $webPush = $this->webPush ?? new WebPush(
            // rely on default auth from config; the concrete app should bind WebPush with auth
            []
        );

        // Expect payload to include subscriptions in metadata under 'subscriptions'
        $payload = $message->toArray();

        $subscriptions = $payload['payload']['data']['subscriptions'] ?? [];

        if (empty($subscriptions) || ! is_array($subscriptions)) {
            return ChannelResult::failure('webpush', null, ['message' => 'no subscriptions available']);
        }

        $sent = 0;

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create($sub);
                $webPush->queueNotification($subscription, json_encode($payload['payload']));
                $sent++;
            } catch (\Throwable $e) {
                // log or continue
            }
        }

        if ($sent === 0) {
            return ChannelResult::failure('webpush', null, ['message' => 'no notifications sent']);
        }

        // Note: we do not flush/send here synchronously to keep this example simple.
        return ChannelResult::success('webpush', null, ['message' => 'queued', 'sent' => $sent]);
    }
}
