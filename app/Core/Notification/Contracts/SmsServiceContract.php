<?php

namespace App\Core\Notification\Contracts;

interface SmsServiceContract
{
    /**
     * Whether the service has valid credentials configured.
     */
    public function isConfigured(): bool;

    /**
     * Send an SMS to a single E.164 phone number.
     *
     * Implementations are responsible for:
     *   - Consent verification (skip or request consent if not opted in).
     *   - Auth token lifecycle.
     *   - Rate-limit and retry handling.
     *
     * Returns a provider-specific result array on success, or an empty array
     * when the message was intentionally withheld (e.g. awaiting consent).
     *
     * @param  string  $to       E.164 formatted number (e.g. +12125551234)
     * @param  string  $message  Plain-text message body
     * @return array<string, string>
     */
    public function send(string $to, string $message): array;
}
