<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Contracts\EmailMessageInterface;
use App\Core\Notification\Contracts\EmailServiceInterface;
use Illuminate\Support\Facades\Log;

final class NullEmailService implements EmailServiceInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(EmailMessageInterface $message): bool
    {
        Log::info('NullEmailService: email skipped because no provider is configured.', [
            'to' => $message->to(),
            'subject' => $message->subject(),
        ]);

        return false;
    }
}
