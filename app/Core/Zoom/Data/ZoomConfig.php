<?php

namespace App\Core\Zoom\Data;

class ZoomConfig
{
    public readonly ?string $accountId;

    public readonly ?string $clientId;

    public readonly ?string $clientSecret;

    public readonly ?string $fromNumber;

    /** Zoom user ID that owns the from_number — required for opt-status checks. */
    public readonly ?string $zoomUserId;

    /** SMS Campaign ID — required for PATCH opt-status updates. */
    public readonly ?string $smsCampaignId;

    public readonly string $tokenUrl;

    public readonly string $apiBaseUrl;

    public readonly int $tokenCacheTtl;

    public readonly int $timeout;

    public readonly int $retryTimes;

    public readonly int $retrySleepMs;

    public function __construct()
    {
        /** @var array{account_id?:string,client_id?:string,client_secret?:string,from_number?:string,token_url:string,api_base_url:string,token_cache_ttl:int,timeout:int,retry_times:int,retry_sleep_ms:int} $cfg */
        $cfg = config('services.zoom', []);

        $this->accountId = $cfg['account_id'] ?? null;
        $this->clientId = $cfg['client_id'] ?? null;
        $this->clientSecret = $cfg['client_secret'] ?? null;
        $this->fromNumber = $cfg['from_number'] ?? null;
        $this->zoomUserId = $cfg['zoom_user_id'] ?? null;
        $this->smsCampaignId = $cfg['sms_campaign_id'] ?? null;
        $this->tokenUrl = $cfg['token_url'] ?? 'https://zoom.us/oauth/token';
        $this->apiBaseUrl = $cfg['api_base_url'] ?? 'https://api.zoom.us/v2';
        $this->tokenCacheTtl = (int) ($cfg['token_cache_ttl'] ?? 3590);
        $this->timeout = (int) ($cfg['timeout'] ?? 15);
        $this->retryTimes = (int) ($cfg['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($cfg['retry_sleep_ms'] ?? 1000);
    }

    public function isConfigured(): bool
    {
        return $this->accountId !== null
            && $this->accountId !== ''
            && $this->clientId !== null
            && $this->clientId !== ''
            && $this->clientSecret !== null
            && $this->clientSecret !== ''
            && $this->fromNumber !== null
            && $this->fromNumber !== '';
    }

    public function canCheckConsentViaApi(): bool
    {
        return $this->zoomUserId !== null && $this->zoomUserId !== '';
    }

    public function canUpdateConsentViaApi(): bool
    {
        return $this->smsCampaignId !== null && $this->smsCampaignId !== '';
    }
}
