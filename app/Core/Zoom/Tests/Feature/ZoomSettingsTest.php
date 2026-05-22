<?php

use App\Core\Settings\Facades\Settings;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Zoom\Data\ZoomConfig;

it('discovers zoom settings definitions through the domain settings synchronizer', function (): void {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();
    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)
        ->toContain('zoom.account_id')
        ->toContain('zoom.client_id')
        ->toContain('zoom.client_secret')
        ->toContain('zoom.token_url')
        ->toContain('zoom.from_number')
        ->toContain('zoom.zoom_user_id')
        ->toContain('zoom.sms_campaign_id')
        ->toContain('zoom.api_base_url')
        ->toContain('zoom.token_cache_ttl')
        ->toContain('zoom.timeout')
        ->toContain('zoom.retry_times')
        ->toContain('zoom.retry_sleep_ms');
});

it('resolves zoom runtime config from saved settings', function (): void {
    Settings::set('zoom.account_id', 'runtime-account-id');
    Settings::set('zoom.client_id', 'runtime-client-id');
    Settings::set('zoom.client_secret', 'runtime-client-secret');
    Settings::set('zoom.from_number', '+15551234567');
    Settings::set('zoom.zoom_user_id', 'runtime-user-id');
    Settings::set('zoom.sms_campaign_id', 'runtime-campaign-id');
    Settings::set('zoom.token_url', 'https://zoom.example.test/oauth/token');
    Settings::set('zoom.api_base_url', 'https://api.zoom.example.test/v2');
    Settings::set('zoom.token_cache_ttl', '3000');
    Settings::set('zoom.timeout', '20');
    Settings::set('zoom.retry_times', '4');
    Settings::set('zoom.retry_sleep_ms', '1500');

    app()->forgetInstance(ZoomConfig::class);

    $zoomConfig = app(ZoomConfig::class);

    expect($zoomConfig->accountId)->toBe('runtime-account-id')
        ->and($zoomConfig->clientId)->toBe('runtime-client-id')
        ->and($zoomConfig->clientSecret)->toBe('runtime-client-secret')
        ->and($zoomConfig->fromNumber)->toBe('+15551234567')
        ->and($zoomConfig->zoomUserId)->toBe('runtime-user-id')
        ->and($zoomConfig->smsCampaignId)->toBe('runtime-campaign-id')
        ->and($zoomConfig->tokenUrl)->toBe('https://zoom.example.test/oauth/token')
        ->and($zoomConfig->apiBaseUrl)->toBe('https://api.zoom.example.test/v2')
        ->and($zoomConfig->tokenCacheTtl)->toBe(3000)
        ->and($zoomConfig->timeout)->toBe(20)
        ->and($zoomConfig->retryTimes)->toBe(4)
        ->and($zoomConfig->retrySleepMs)->toBe(1500);

    Settings::set('zoom.account_id', '');
    Settings::set('zoom.client_id', '');
    Settings::set('zoom.client_secret', '');
    Settings::set('zoom.from_number', '');
    Settings::set('zoom.zoom_user_id', '');
    Settings::set('zoom.sms_campaign_id', '');
    Settings::set('zoom.token_url', '');
    Settings::set('zoom.api_base_url', '');
    Settings::set('zoom.token_cache_ttl', '');
    Settings::set('zoom.timeout', '');
    Settings::set('zoom.retry_times', '');
    Settings::set('zoom.retry_sleep_ms', '');
});

it('stores zoom settings in a dedicated services zoom group', function (): void {
    app(DomainSettingsSynchronizer::class)->sync();

    $zoomSetting = SettingsSqlite::query()->where('key', 'zoom.account_id')->first();

    expect($zoomSetting)->not->toBeNull()
        ->and($zoomSetting?->group)->toBe('services.zoom');
});
