<?php

namespace App\Core\Zoom\Services;

use App\Core\Zoom\Data\ZoomConfig;
use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Models\ZoomSmsConsent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages SMS opt-in consent for Zoom Phone recipients.
 *
 * Local DB (`zoom_sms_consents`) is the authoritative source at send-time.
 * The Zoom API is used to seed initial state and to record changes when
 * a recipient replies YES / NO (call markOptedIn / markOptedOut from your
 * webhook handler, or use syncFromZoom on first encounter).
 *
 * Zoom API used here:
 *   GET  /phone/user/{userId}/sms_campaigns/phone_numbers/opt_status
 *        ? consumer_phone_numbers[]=<recipient>
 *          zoom_phone_user_numbers[]=<our from_number>
 *   PATCH /phone/sms_campaigns/{smsCampaignId}/phone_numbers/opt_status
 *        ? update status for a pair when someone explicitly opts in/out
 */
class ZoomSmsConsentService
{
    public function __construct(
        private readonly ZoomConfig $config,
        private readonly ZoomTokenService $tokenService,
    ) {}

    /**
     * Current local consent status, or null when the number is unknown.
     */
    public function getStatus(string $phoneNumber): ?SmsConsentStatus
    {
        return ZoomSmsConsent::where('phone_number', $phoneNumber)->first()?->status;
    }

    /**
     * Whether the number is confirmed opted-in and may receive messages.
     */
    public function canReceive(string $phoneNumber): bool
    {
        return $this->getStatus($phoneNumber) === SmsConsentStatus::OptedIn;
    }

    /**
     * Whether the number has explicitly opted out.
     */
    public function hasOptedOut(string $phoneNumber): bool
    {
        return $this->getStatus($phoneNumber) === SmsConsentStatus::OptedOut;
    }

    /**
     * Sends a consent-request SMS and records the number as `pending`.
     *
     * Uses ZoomSmsService::sendRaw() so the consent guard is bypassed for
     * this one system-initiated message.
     *
     * @param  ZoomSmsService  $smsService  Injected at call-site to avoid a circular dependency.
     */
    public function requestConsent(string $phoneNumber, ZoomSmsService $smsService): void
    {
        $smsService->sendRaw($phoneNumber, $this->consentMessage());

        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'status' => SmsConsentStatus::Pending,
                'consent_requested_at' => now(),
            ]
        );

        Log::info('Zoom SMS consent request sent.', ['phone_number' => $phoneNumber]);
    }

    /**
     * Marks a number as opted-in and optionally syncs back to Zoom.
     * Call this from your webhook / inbound-SMS handler when a user replies YES.
     */
    public function markOptedIn(string $phoneNumber): void
    {
        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'status' => SmsConsentStatus::OptedIn,
                'consented_at' => now(),
                'declined_at' => null,
            ]
        );

        $this->patchZoomOptStatus($phoneNumber, 'opt_in');

        Log::info('Zoom SMS consent opted-in.', ['phone_number' => $phoneNumber]);
    }

    /**
     * Marks a number as opted-out and optionally syncs back to Zoom.
     * Call this from your webhook / inbound-SMS handler when a user replies NO/STOP.
     */
    public function markOptedOut(string $phoneNumber): void
    {
        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'status' => SmsConsentStatus::OptedOut,
                'declined_at' => now(),
                'consented_at' => null,
            ]
        );

        $this->patchZoomOptStatus($phoneNumber, 'opt_out');

        Log::info('Zoom SMS consent opted-out.', ['phone_number' => $phoneNumber]);
    }

    /**
     * Checks the Zoom API for an existing opt-status record for this number
     * and writes the result locally. Returns the resolved status, or null when
     * Zoom has no record or the feature is not configured.
     *
     * Endpoint: GET /phone/user/{userId}/sms_campaigns/phone_numbers/opt_status
     * Rate Limit Label: LIGHT
     *
     * @param  string  $phoneNumber  Recipient number in E.164 format (+1XXXXXXXXXX)
     */
    public function syncFromZoom(string $phoneNumber): ?SmsConsentStatus
    {
        if (! $this->config->canCheckConsentViaApi()) {
            return null;
        }

        try {
            $token = $this->tokenService->accessToken();
            $userId = $this->config->zoomUserId;
            $fromNumber = ltrim((string) $this->config->fromNumber, '+');
            $toNumber = ltrim($phoneNumber, '+');

            $response = Http::timeout($this->config->timeout)
                ->withToken($token)
                ->acceptJson()
                ->get("{$this->config->apiBaseUrl}/phone/user/{$userId}/sms_campaigns/phone_numbers/opt_status", [
                    'consumer_phone_numbers' => [$toNumber],
                    'zoom_phone_user_numbers' => [$fromNumber],
                ]);

            if (! $response->successful()) {
                Log::warning('Zoom opt-status check failed.', [
                    'phone_number' => $phoneNumber,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            /**
             * @var array{
             *   phone_number_campaign_opt_statuses?: array<int, array{
             *     consumer_phone_number: string,
             *     zoom_phone_user_number: string,
             *     opt_status: string,
             *     opt_in_status: int,
             *     opt_in_message?: string
             *   }>
             * } $data
             */
            $data = $response->json();
            $entries = $data['phone_number_campaign_opt_statuses'] ?? [];

            if (empty($entries)) {
                return null;
            }

            $status = match ($entries[0]['opt_status'] ?? '') {
                'opt_in' => SmsConsentStatus::OptedIn,
                'opt_out' => SmsConsentStatus::OptedOut,
                default => null,
            };

            if ($status === SmsConsentStatus::OptedIn) {
                ZoomSmsConsent::updateOrCreate(
                    ['phone_number' => $phoneNumber],
                    ['status' => SmsConsentStatus::OptedIn, 'consented_at' => now(), 'declined_at' => null]
                );
            } elseif ($status === SmsConsentStatus::OptedOut) {
                ZoomSmsConsent::updateOrCreate(
                    ['phone_number' => $phoneNumber],
                    ['status' => SmsConsentStatus::OptedOut, 'declined_at' => now(), 'consented_at' => null]
                );
            }

            return $status;
        } catch (\Throwable $exception) {
            Log::warning('Zoom opt-status sync threw an exception.', [
                'phone_number' => $phoneNumber,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Pushes an opt status change back to Zoom's campaign record.
     * Silently skipped when no campaign ID is configured.
     *
     * Endpoint: PATCH /phone/sms_campaigns/{smsCampaignId}/phone_numbers/opt_status
     * Rate Limit Label: LIGHT
     */
    private function patchZoomOptStatus(string $phoneNumber, string $optStatus): void
    {
        if (! $this->config->canUpdateConsentViaApi()) {
            return;
        }

        try {
            $token = $this->tokenService->accessToken();
            $campaignId = $this->config->smsCampaignId;
            $fromNumber = ltrim((string) $this->config->fromNumber, '+');
            $toNumber = ltrim($phoneNumber, '+');

            $response = Http::timeout($this->config->timeout)
                ->withToken($token)
                ->acceptJson()
                ->patch("{$this->config->apiBaseUrl}/phone/sms_campaigns/{$campaignId}/phone_numbers/opt_status", [
                    'consumer_phone_number' => $toNumber,
                    'zoom_phone_user_numbers' => [$fromNumber],
                    'opt_status' => $optStatus,
                ]);

            if (! $response->successful() && $response->status() !== 204) {
                Log::warning('Zoom PATCH opt-status failed.', [
                    'phone_number' => $phoneNumber,
                    'opt_status' => $optStatus,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Zoom PATCH opt-status threw an exception.', [
                'phone_number' => $phoneNumber,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function consentMessage(): string
    {
        /** @var string $appName */
        $appName = config('app.name', 'Our Service');

        return "Reply YES to receive SMS notifications from {$appName}. Reply NO to opt out. Msg & data rates may apply.";
    }
}
