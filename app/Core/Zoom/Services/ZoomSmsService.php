<?php

namespace App\Core\Zoom\Services;

use App\Core\Notification\Contracts\SmsServiceContract;
use App\Core\Zoom\Data\ZoomConfig;
use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Exceptions\ZoomSmsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS messages via the Zoom Phone API.
 *
 * Endpoint: POST /phone/sms/messages
 * Rate Limit Label: MEDIUM (10 req/s Pro, 20 req/s Business+)
 * Required scope: phone_sms:write:admin
 *
 * Retry strategy:
 *   - 401 Unauthorized → force-refresh the token and retry once.
 *   - 429 Too Many Requests → throw ZoomSmsException::rateLimitExceeded().
 *   - Other non-2xx → throw ZoomSmsException::sendFailed().
 */
class ZoomSmsService implements SmsServiceContract
{
    public function __construct(
        private readonly ZoomConfig $config,
        private readonly ZoomTokenService $tokenService,
        private readonly ZoomSmsConsentService $consentService,
    ) {}

    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    /**
     * Send an SMS to a single recipient.
     *
     * @param  string  $to  E.164 formatted phone number (e.g. +12125551234)
     * @param  string  $message  Plain text message (max 500 chars per segment)
     * @return array{message_id: string, session_id: string}
     *
     * @throws ZoomSmsException
     */
    public function send(string $to, string $message): array
    {
        if (! $this->isConfigured()) {
            throw ZoomSmsException::notConfigured();
        }

        $normalizedTo = $this->normalizeUsPhoneNumber($to);

        // --- Consent gate -------------------------------------------------------
        $status = $this->consentService->getStatus($normalizedTo);

        if ($status === null) {
            // Number not in local DB — check Zoom's API for an existing record.
            $status = $this->consentService->syncFromZoom($normalizedTo);
        }

        if ($status === SmsConsentStatus::OptedOut) {
            Log::info('Zoom SMS blocked: recipient has opted out.', ['to' => $normalizedTo]);

            return [];
        }

        if ($status === null || $status === SmsConsentStatus::Pending) {
            // No record at all, or only pending — send consent request and hold.
            $this->consentService->requestConsent($normalizedTo, $this);

            return [];
        }
        // -----------------------------------------------------------------------

        return $this->doSend($normalizedTo, $message, tokenRefreshed: false);
    }

    /**
     * Send an SMS bypassing the consent gate.
     *
     * Use only for system-initiated messages where consent cannot be pre-checked
     * (e.g. the initial consent-request message sent by ZoomSmsConsentService).
     *
     * @return array{message_id: string, session_id: string}
     *
     * @throws ZoomSmsException
     */
    public function sendRaw(string $to, string $message): array
    {
        if (! $this->isConfigured()) {
            throw ZoomSmsException::notConfigured();
        }

        $normalizedTo = $this->normalizeUsPhoneNumber($to);

        return $this->doSend($normalizedTo, $message, tokenRefreshed: false);
    }

    /**
     * @return array{message_id: string, session_id: string}
     *
     * @throws ZoomSmsException
     */
    private function doSend(string $to, string $message, bool $tokenRefreshed): array
    {
        $normalizedFrom = $this->normalizeUsPhoneNumber((string) $this->config->fromNumber);

        $token = $tokenRefreshed
            ? $this->tokenService->forceRefresh()
            : $this->tokenService->accessToken();

        try {
            $response = Http::timeout($this->config->timeout)
                ->withToken($token)
                ->acceptJson()
                ->post("{$this->config->apiBaseUrl}/phone/sms/messages", [
                    'sender' => [
                        'phone_number' => $normalizedFrom,
                    ],
                    'to_members' => [
                        ['phone_number' => $to],
                    ],
                    'message' => $message,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Zoom SMS connection error.', [
                'to' => $to,
                'error' => $exception->getMessage(),
            ]);

            throw ZoomSmsException::sendFailed(0, $exception->getMessage());
        }

        // Token expired — refresh once and retry.
        if ($response->status() === 401 && ! $tokenRefreshed) {
            Log::warning('Zoom SMS received 401; refreshing token and retrying.', ['to' => $to]);

            return $this->doSend($to, $message, tokenRefreshed: true);
        }

        if ($response->status() === 429) {
            Log::warning('Zoom SMS rate limit hit (429).', ['to' => $to]);

            throw ZoomSmsException::rateLimitExceeded();
        }

        if (! $response->successful()) {
            Log::error('Zoom SMS send failed.', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ZoomSmsException::sendFailed($response->status(), $response->body());
        }

        /** @var array{message_id?: string, session_id?: string} $data */
        $data = $response->json();

        Log::info('Zoom SMS sent successfully.', [
            'to' => $to,
            'message_id' => $data['message_id'] ?? null,
        ]);

        return [
            'message_id' => $data['message_id'] ?? '',
            'session_id' => $data['session_id'] ?? '',
        ];
    }

    /**
     * Normalize a US phone number to E.164 (+1XXXXXXXXXX) for Zoom APIs.
     *
     * Accepted formats:
     * - +1XXXXXXXXXX
     * - 1XXXXXXXXXX
     * - XXXXXXXXXX
     * - Any of the above with separators/spaces.
     *
     * @throws ZoomSmsException
     */
    private function normalizeUsPhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        throw ZoomSmsException::invalidPhoneNumber($phoneNumber);
    }
}
