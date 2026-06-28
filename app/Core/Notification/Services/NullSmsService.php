<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Contracts\SmsMessage;
use App\Core\Notification\Contracts\SmsServiceContract;
use Illuminate\Support\Facades\Log;

final class NullSmsService implements SmsServiceContract
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(string $to, SmsMessage $message): array
    {
        Log::info('NullSmsService: SMS skipped because no provider is configured.', [
            'to' => $to,
            'notification_to' => $message->to(),
        ]);

        return [];
    }
}
