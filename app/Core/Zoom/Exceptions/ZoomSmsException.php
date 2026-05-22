<?php

namespace App\Core\Zoom\Exceptions;

use RuntimeException;

class ZoomSmsException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self(
            'Zoom SMS service is not configured. Ensure ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID, '.
            'ZOOM_CLIENT_SECRET, and ZOOM_SMS_FROM_NUMBER are set.'
        );
    }

    public static function tokenRequestFailed(string $reason): self
    {
        return new self("Failed to obtain Zoom S2S access token: {$reason}");
    }

    public static function rateLimitExceeded(): self
    {
        return new self('Zoom SMS rate limit exceeded (HTTP 429). Message was not sent.');
    }

    public static function sendFailed(int $status, string $body): self
    {
        return new self("Zoom SMS send failed (HTTP {$status}): {$body}");
    }

    public static function campaignIdRequired(): self
    {
        return new self(
            'Zoom SMS campaign ID is required. Set ZOOM_SMS_CAMPAIGN_ID (or zoom.sms_campaign_id setting).'
        );
    }

    public static function apiRequestFailed(string $operation, int $status, string $body): self
    {
        return new self("Zoom SMS {$operation} failed (HTTP {$status}): {$body}");
    }

    public static function invalidPhoneNumber(string $phoneNumber): self
    {
        return new self(
            "Invalid phone number format for Zoom SMS: {$phoneNumber}. Expected US E.164 (+1XXXXXXXXXX), 10 digits, or 11 digits starting with 1."
        );
    }
}
