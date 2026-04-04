<?php

namespace App\Core\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            return;
        }

        $payload = $notification->toPush($notifiable);

        if (! is_array($payload) || ($payload['title'] ?? null) === null || ($payload['body'] ?? null) === null) {
            return;
        }

        // Push provider wiring is intentionally deferred for MVP plumbing.
        Log::info('Push notification queued for provider integration.', [
            'notification' => get_class($notification),
            'title' => (string) $payload['title'],
        ]);
    }
}
