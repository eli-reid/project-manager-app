<?php

namespace App\Core\Notification\Contracts;

interface SmsServiceContract
{
    public function isConfigured(): bool;

    /**
     * @return array{message_id?: string, session_id?: string}
     */
    public function send(string $to, SmsMessage $message): array;
}
