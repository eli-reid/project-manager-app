<?php

namespace App\PlugIns\Zoom\Services;

use App\PlugIns\Zoom\Data\ZoomConfig;
use App\PlugIns\Zoom\Enums\SmsConsentStatus;
use App\PlugIns\Zoom\Exceptions\ZoomSmsException;
use App\PlugIns\Zoom\Models\ZoomSmsConsent;
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
        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);

        return ZoomSmsConsent::where('phone_number', $normalizedPhoneNumber)->first()?->status;
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
        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);

        $smsService->sendRaw($normalizedPhoneNumber, $this->consentMessage());

        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $normalizedPhoneNumber],
            [
                'status' => SmsConsentStatus::Pending,
                'consent_requested_at' => now(),
            ]
        );

        Log::info('Zoom SMS consent request sent.', ['phone_number' => $normalizedPhoneNumber]);
    }

    /**
     * Marks a number as opted-in and optionally syncs back to Zoom.
     * Call this from your webhook / inbound-SMS handler when a user replies YES.
     */
    public function markOptedIn(string $phoneNumber): void
    {
        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);

        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $normalizedPhoneNumber],
            [
                'status' => SmsConsentStatus::OptedIn,
                'consented_at' => now(),
                'declined_at' => null,
            ]
        );

        $this->patchZoomOptStatus($normalizedPhoneNumber, 'opt_in');

        Log::info('Zoom SMS consent opted-in.', ['phone_number' => $normalizedPhoneNumber]);
    }

    /**
     * Marks a number as opted-out and optionally syncs back to Zoom.
     * Call this from your webhook / inbound-SMS handler when a user replies NO/STOP.
     */
    public function markOptedOut(string $phoneNumber): void
    {
        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);

        ZoomSmsConsent::updateOrCreate(
            ['phone_number' => $normalizedPhoneNumber],
            [
                'status' => SmsConsentStatus::OptedOut,
                'declined_at' => now(),
                'consented_at' => null,
            ]
        );

        $this->patchZoomOptStatus($normalizedPhoneNumber, 'opt_out');

        Log::info('Zoom SMS consent opted-out.', ['phone_number' => $normalizedPhoneNumber]);
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
        $result = $this->syncFromZoomWithResponse($phoneNumber);

        return $result['status'];
    }

    /**
     * Checks the Zoom API for an existing opt-status record for a single number,
     * writes the result locally, and returns a detailed response payload.
     *
     * @param  string  $phoneNumber  Recipient number in E.164 format (+1XXXXXXXXXX)
     * @return array{
     *   status: SmsConsentStatus|null,
     *   response_status: int|null,
     *   response_json: array<string, mixed>|null,
     *   response_body: string|null,
     *   request_phone_number: string,
     *   request_consumer_phone_number: string|null,
     *   request_zoom_phone_user_number: string|null,
     *   error: string|null
     * }
     */
    public function syncFromZoomWithResponse(string $phoneNumber): array
    {
        if (! $this->config->canCheckConsentViaApi()) {
            return [
                'status' => null,
                'response_status' => null,
                'response_json' => null,
                'response_body' => null,
                'request_phone_number' => $phoneNumber,
                'request_consumer_phone_number' => null,
                'request_zoom_phone_user_number' => null,
                'error' => 'Zoom consent lookup is not configured. Missing zoom_user_id and/or from_number.',
            ];
        }

        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);
        $fromNumber = null;
        $toNumber = null;

        try {
            $token = $this->tokenService->accessToken();
            $userId = $this->config->zoomUserId;
            $fromNumber = $this->toZoomApiNumber((string) $this->config->fromNumber);
            $toNumber = $this->toZoomApiNumber($normalizedPhoneNumber);

            $response = Http::timeout($this->config->timeout)
                ->withToken($token)
                ->acceptJson()
                ->get("{$this->config->apiBaseUrl}/phone/user/{$userId}/sms_campaigns/phone_numbers/opt_status", [
                    'consumer_phone_numbers' => [$toNumber],
                    'zoom_phone_user_numbers' => [$fromNumber],
                ]);

            if (! $response->successful()) {
                Log::warning('Zoom opt-status check failed.', [
                    'phone_number' => $normalizedPhoneNumber,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                /** @var array<string, mixed>|null $errorJson */
                $errorJson = $response->json();

                return [
                    'status' => null,
                    'response_status' => $response->status(),
                    'response_json' => is_array($errorJson) ? $errorJson : null,
                    'response_body' => $response->body(),
                    'request_phone_number' => $normalizedPhoneNumber,
                    'request_consumer_phone_number' => $toNumber,
                    'request_zoom_phone_user_number' => $fromNumber,
                    'error' => null,
                ];
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
                return [
                    'status' => null,
                    'response_status' => $response->status(),
                    'response_json' => is_array($data) ? $data : null,
                    'response_body' => $response->body(),
                    'request_phone_number' => $normalizedPhoneNumber,
                    'request_consumer_phone_number' => $toNumber,
                    'request_zoom_phone_user_number' => $fromNumber,
                    'error' => null,
                ];
            }

            $status = match ($entries[0]['opt_status'] ?? '') {
                'opt_in' => SmsConsentStatus::OptedIn,
                'opt_out' => SmsConsentStatus::OptedOut,
                default => null,
            };

            if ($status === SmsConsentStatus::OptedIn) {
                ZoomSmsConsent::updateOrCreate(
                    ['phone_number' => $normalizedPhoneNumber],
                    ['status' => SmsConsentStatus::OptedIn, 'consented_at' => now(), 'declined_at' => null]
                );
            } elseif ($status === SmsConsentStatus::OptedOut) {
                ZoomSmsConsent::updateOrCreate(
                    ['phone_number' => $normalizedPhoneNumber],
                    ['status' => SmsConsentStatus::OptedOut, 'declined_at' => now(), 'consented_at' => null]
                );
            }

            return [
                'status' => $status,
                'response_status' => $response->status(),
                'response_json' => is_array($data) ? $data : null,
                'response_body' => $response->body(),
                'request_phone_number' => $normalizedPhoneNumber,
                'request_consumer_phone_number' => $toNumber,
                'request_zoom_phone_user_number' => $fromNumber,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Zoom opt-status sync threw an exception.', [
                'phone_number' => $normalizedPhoneNumber,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => null,
                'response_status' => null,
                'response_json' => null,
                'response_body' => null,
                'request_phone_number' => $normalizedPhoneNumber,
                'request_consumer_phone_number' => $toNumber,
                'request_zoom_phone_user_number' => $fromNumber,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Lists phone number opt statuses for the configured SMS campaign.
     *
     * Endpoint: GET /phone/sms_campaigns/{smsCampaignId}/phone_numbers/opt_status
     * Rate Limit Label: LIGHT
     *
     * @param  int|null  $pageSize  Optional page size for paginated results.
     * @param  string|null  $nextPageToken  Optional token returned by a previous call.
     * @return array{
     *   phone_number_campaign_opt_statuses: array<int, array<string, mixed>>,
     *   next_page_token: string
     * }
     *
     * @throws ZoomSmsException
     */
    public function listCampaignPhoneNumberOptStatuses(?int $pageSize = null, ?string $nextPageToken = null): array
    {
        if (! $this->config->canUpdateConsentViaApi()) {
            throw ZoomSmsException::campaignIdRequired();
        }

        $campaignId = (string) $this->config->smsCampaignId;
        $token = $this->tokenService->accessToken();

        $query = [];

        if ($pageSize !== null) {
            $query['page_size'] = max(1, $pageSize);
        }

        if (is_string($nextPageToken) && $nextPageToken !== '') {
            $query['next_page_token'] = $nextPageToken;
        }

        $response = Http::timeout($this->config->timeout)
            ->withToken($token)
            ->acceptJson()
            ->get("{$this->config->apiBaseUrl}/phone/sms_campaigns/{$campaignId}/phone_numbers/opt_status", $query);

        if ($response->status() === 429) {
            throw ZoomSmsException::rateLimitExceeded();
        }

        if (! $response->successful()) {
            Log::warning('Zoom GET campaign phone number opt statuses failed.', [
                'campaign_id' => $campaignId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ZoomSmsException::apiRequestFailed('campaign opt-status list', $response->status(), $response->body());
        }

        /** @var array{phone_number_campaign_opt_statuses?: array<int, array<string, mixed>>, next_page_token?: string} $data */
        $data = $response->json();

        $entries = $data['phone_number_campaign_opt_statuses'] ?? [];

        return [
            'phone_number_campaign_opt_statuses' => is_array($entries) ? $entries : [],
            'next_page_token' => (string) ($data['next_page_token'] ?? ''),
        ];
    }

    /**
     * Synchronize local consent rows from Zoom campaign opt-status pages.
     *
     * @param  int  $pageSize  Zoom API page size.
     * @param  int  $maxPages  Safety cap to avoid unbounded loops.
     * @return array{processed:int,opted_in:int,opted_out:int,unknown:int,next_page_token:string}
     *
     * @throws ZoomSmsException
     */
    public function syncCampaignConsentStatuses(int $pageSize = 100, int $maxPages = 10): array
    {
        $pageSize = max(1, $pageSize);
        $maxPages = max(1, $maxPages);

        $processed = 0;
        $optedIn = 0;
        $optedOut = 0;
        $unknown = 0;
        $nextPageToken = null;

        for ($page = 1; $page <= $maxPages; $page++) {
            $result = $this->listCampaignPhoneNumberOptStatuses($pageSize, $nextPageToken);

            /** @var array<int, array<string, mixed>> $entries */
            $entries = $result['phone_number_campaign_opt_statuses'] ?? [];

            foreach ($entries as $entry) {
                $consumerPhoneNumber = trim((string) ($entry['consumer_phone_number'] ?? ''));

                if ($consumerPhoneNumber === '') {
                    $unknown++;

                    continue;
                }

                try {
                    $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($consumerPhoneNumber);
                } catch (ZoomSmsException $exception) {
                    Log::warning('Skipping invalid phone number from Zoom campaign sync.', [
                        'consumer_phone_number' => $consumerPhoneNumber,
                        'error' => $exception->getMessage(),
                    ]);
                    $unknown++;

                    continue;
                }

                $optStatus = (string) ($entry['opt_status'] ?? '');

                if ($optStatus === 'opt_in') {
                    ZoomSmsConsent::updateOrCreate(
                        ['phone_number' => $normalizedPhoneNumber],
                        ['status' => SmsConsentStatus::OptedIn, 'consented_at' => now(), 'declined_at' => null]
                    );
                    $optedIn++;
                    $processed++;

                    continue;
                }

                if ($optStatus === 'opt_out') {
                    ZoomSmsConsent::updateOrCreate(
                        ['phone_number' => $normalizedPhoneNumber],
                        ['status' => SmsConsentStatus::OptedOut, 'declined_at' => now(), 'consented_at' => null]
                    );
                    $optedOut++;
                    $processed++;

                    continue;
                }

                $unknown++;
            }

            $nextPageToken = (string) ($result['next_page_token'] ?? '');

            if ($nextPageToken === '') {
                break;
            }
        }

        return [
            'processed' => $processed,
            'opted_in' => $optedIn,
            'opted_out' => $optedOut,
            'unknown' => $unknown,
            'next_page_token' => (string) ($nextPageToken ?? ''),
        ];
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

        $normalizedPhoneNumber = $this->normalizeUsPhoneNumber($phoneNumber);

        try {
            $token = $this->tokenService->accessToken();
            $campaignId = $this->config->smsCampaignId;
            $fromNumber = $this->toZoomApiNumber((string) $this->config->fromNumber);
            $toNumber = $this->toZoomApiNumber($normalizedPhoneNumber);

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
                    'phone_number' => $normalizedPhoneNumber,
                    'opt_status' => $optStatus,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Zoom PATCH opt-status threw an exception.', [
                'phone_number' => $normalizedPhoneNumber,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Normalize a US phone number to E.164 (+1XXXXXXXXXX) for Zoom APIs.
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

    /**
     * Return Zoom API phone value format for campaign/opt-status endpoints.
     *
     * @throws ZoomSmsException
     */
    private function toZoomApiNumber(string $phoneNumber): string
    {
        return ltrim($this->normalizeUsPhoneNumber($phoneNumber), '+');
    }

    private function consentMessage(): string
    {
        /** @var string $appName */
        $appName = config('app.name', 'Our Service');

        return "Reply YES to receive SMS notifications from {$appName}. Reply NO to opt out. Msg & data rates may apply.";
    }
}
