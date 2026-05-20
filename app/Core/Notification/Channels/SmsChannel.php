<?php

namespace App\Core\Notification\Channels;

use App\Core\Zoom\Exceptions\ZoomSmsException;
use App\Core\Zoom\Services\ZoomSmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function __construct(private readonly ZoomSmsService $smsService) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $payload = $notification->toSms($notifiable);

        if (! is_array($payload) || ($payload['to'] ?? null) === null || ($payload['message'] ?? null) === null) {
            return;
        }

        if (! $this->smsService->isConfigured()) {
            Log::warning('Zoom SMS is not configured; skipping SMS notification.', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        try {
            $this->smsService->send((string) $payload['to'], (string) $payload['message']);
        } catch (ZoomSmsException $exception) {
            Log::error('SMS notification failed to send via Zoom.', [
                'notification' => get_class($notification),
                'to' => (string) $payload['to'],
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
