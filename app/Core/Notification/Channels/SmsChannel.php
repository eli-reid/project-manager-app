<?php

namespace App\Core\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }
        $payload = $notification->toSms($notifiable);

        if (! is_array($payload) || ($payload['to'] ?? null) === null || ($payload['message'] ?? null) === null) {
            return;
        }

        // SMS provider wiring is intentionally deferred for MVP plumbing.
        Log::info('SMS notification queued for provider integration.', [
            'notification' => get_class($notification),
            'to' => (string) $payload['to'],
        ]);
    }
}
