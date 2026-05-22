<?php

namespace App\Core\Zoom\Data;

use App\Core\Settings\Facades\Settings;

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
        $this->accountId = $this->nullableStringSetting('zoom.account_id', 'services.zoom.account_id');
        $this->clientId = $this->nullableStringSetting('zoom.client_id', 'services.zoom.client_id');
        $this->clientSecret = $this->nullableStringSetting('zoom.client_secret', 'services.zoom.client_secret');
        $this->fromNumber = $this->nullableStringSetting('zoom.from_number', 'services.zoom.from_number');
        $this->zoomUserId = $this->nullableStringSetting('zoom.zoom_user_id', 'services.zoom.zoom_user_id');
        $this->smsCampaignId = $this->nullableStringSetting('zoom.sms_campaign_id', 'services.zoom.sms_campaign_id');
        $this->tokenUrl = $this->stringSetting('zoom.token_url', 'services.zoom.token_url', 'https://zoom.us/oauth/token');
        $this->apiBaseUrl = $this->stringSetting('zoom.api_base_url', 'services.zoom.api_base_url', 'https://api.zoom.us/v2');
        $this->tokenCacheTtl = $this->intSetting('zoom.token_cache_ttl', 'services.zoom.token_cache_ttl', 3590);
        $this->timeout = $this->intSetting('zoom.timeout', 'services.zoom.timeout', 15);
        $this->retryTimes = $this->intSetting('zoom.retry_times', 'services.zoom.retry_times', 3);
        $this->retrySleepMs = $this->intSetting('zoom.retry_sleep_ms', 'services.zoom.retry_sleep_ms', 1000);
    }

    private function nullableStringSetting(string $settingKey, string $configKey): ?string
    {
        $settingValue = Settings::get($settingKey, null)->raw();

        if (is_string($settingValue) && trim($settingValue) !== '') {
            return $settingValue;
        }

        $configValue = config($configKey);

        if (is_string($configValue) && trim($configValue) !== '') {
            return $configValue;
        }

        return null;
    }

    private function stringSetting(string $settingKey, string $configKey, string $default): string
    {
        $settingValue = Settings::get($settingKey, null)->raw();

        if (is_string($settingValue) && trim($settingValue) !== '') {
            return $settingValue;
        }

        return (string) config($configKey, $default);
    }

    private function intSetting(string $settingKey, string $configKey, int $default): int
    {
        $settingValue = Settings::get($settingKey, null)->raw();

        if (is_numeric($settingValue)) {
            return (int) $settingValue;
        }

        return (int) config($configKey, $default);
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
