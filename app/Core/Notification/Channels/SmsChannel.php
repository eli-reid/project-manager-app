<?php

namespace App\Core\Notification\Channels;

use App\Core\Notification\Contracts\SmsNotification;
use App\Core\Notification\Contracts\SmsMessage;
use Illuminate\Notifications\Notification;

use Illuminate\Support\Facades\Log;

class SmsChannel implements SmsChannel
{
    public function __construct(private readonly SmsNotification $smsService) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $payload = $notification->toSms($notifiable);

        if (! is_array($payload) || ($payload['to'] ?? null) === null || ($payload['message'] ?? null) === null) {
            Log::warning('SMS notification payload is missing required fields.', [
                'notification' => get_class($notification),
                'has_to' => is_array($payload) && ($payload['to'] ?? null) !== null,
                'has_message' => is_array($payload) && ($payload['message'] ?? null) !== null,
            ]);

            return;
        }

        if (! $this->smsService->isConfigured()) {
            Log::warning('SMS is not configured; skipping SMS notification.', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        try {
            $result = $this->smsService->send((string) $payload['to'], (string) $payload['message']);

            if ($result === []) {
                Log::warning('SMS notification was withheld by consent flow.', [
                    'notification' => get_class($notification),
                    'to' => (string) $payload['to'],
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('SMS notification failed to send.', [
                'notification' => get_class($notification),
                'to' => (string) $payload['to'],
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
                'is_sms_exception' => null,
            ]);
        }
    }
}
