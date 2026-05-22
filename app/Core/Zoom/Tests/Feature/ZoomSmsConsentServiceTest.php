<?php

use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Exceptions\ZoomSmsException;
use App\Core\Zoom\Models\ZoomSmsConsent;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use App\Core\Zoom\Services\ZoomTokenService;
use Illuminate\Support\Facades\Http;

it('lists campaign phone number opt statuses from zoom', function (): void {
    config([
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
        'services.zoom.sms_campaign_id' => 'campaign-123',
        'services.zoom.timeout' => 15,
    ]);

    app()->forgetInstance(ZoomTokenService::class);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        '*phone/sms_campaigns/campaign-123/phone_numbers/opt_status*' => Http::response([
            'phone_number_campaign_opt_statuses' => [
                [
                    'consumer_phone_number' => '12125550001',
                    'zoom_phone_user_number' => '15556667777',
                    'opt_status' => 'opt_in',
                ],
            ],
            'next_page_token' => 'next-token-1',
        ], 200),
        '*' => Http::response([], 200),
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    $result = $service->listCampaignPhoneNumberOptStatuses(pageSize: 25, nextPageToken: 'cursor-1');

    expect($result['phone_number_campaign_opt_statuses'])->toHaveCount(1)
        ->and($result['phone_number_campaign_opt_statuses'][0]['opt_status'])->toBe('opt_in')
        ->and($result['next_page_token'])->toBe('next-token-1');

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), '/phone/sms_campaigns/campaign-123/phone_numbers/opt_status')
            && str_contains($request->url(), 'page_size=25')
            && str_contains($request->url(), 'next_page_token=cursor-1')
            && $request->hasHeader('Authorization', 'Bearer token-123');
    });
});

it('throws when campaign id is not configured for campaign list calls', function (): void {
    config([
        'services.zoom.sms_campaign_id' => null,
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    expect(fn () => $service->listCampaignPhoneNumberOptStatuses())
        ->toThrow(ZoomSmsException::class, 'Zoom SMS campaign ID is required.');
});

it('throws a rate limit exception when campaign list endpoint returns 429', function (): void {
    config([
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
        'services.zoom.sms_campaign_id' => 'campaign-123',
        'services.zoom.timeout' => 15,
    ]);

    app()->forgetInstance(ZoomTokenService::class);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        '*phone/sms_campaigns/campaign-123/phone_numbers/opt_status*' => Http::response([], 429),
        '*' => Http::response([], 200),
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    expect(fn () => $service->listCampaignPhoneNumberOptStatuses())
        ->toThrow(ZoomSmsException::class, 'rate limit exceeded');
});

it('normalizes 10-digit numbers to +1 E.164 for local consent lookups', function (): void {
    ZoomSmsConsent::query()->create([
        'phone_number' => '+12125550001',
        'status' => SmsConsentStatus::OptedIn,
    ]);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    expect($service->getStatus('(212) 555-0001'))->toBe(SmsConsentStatus::OptedIn);
});

it('rejects malformed phone numbers for consent lookups', function (): void {
    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    expect(fn () => $service->getStatus('555-12'))
        ->toThrow(ZoomSmsException::class, 'Invalid phone number format for Zoom SMS');
});

it('syncs campaign consent statuses into local table across pages', function (): void {
    config([
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
        'services.zoom.sms_campaign_id' => 'campaign-123',
        'services.zoom.timeout' => 15,
    ]);

    app()->forgetInstance(ZoomTokenService::class);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->twice()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        '*phone/sms_campaigns/campaign-123/phone_numbers/opt_status?page_size=2' => Http::response([
            'phone_number_campaign_opt_statuses' => [
                [
                    'consumer_phone_number' => '2125551111',
                    'opt_status' => 'opt_in',
                ],
                [
                    'consumer_phone_number' => '2125552222',
                    'opt_status' => 'opt_out',
                ],
            ],
            'next_page_token' => 'next-1',
        ], 200),
        '*phone/sms_campaigns/campaign-123/phone_numbers/opt_status?page_size=2&next_page_token=next-1' => Http::response([
            'phone_number_campaign_opt_statuses' => [
                [
                    'consumer_phone_number' => '55512',
                    'opt_status' => 'opt_in',
                ],
            ],
            'next_page_token' => '',
        ], 200),
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    $result = $service->syncCampaignConsentStatuses(pageSize: 2, maxPages: 5);

    expect($result['processed'])->toBe(2)
        ->and($result['opted_in'])->toBe(1)
        ->and($result['opted_out'])->toBe(1)
        ->and($result['unknown'])->toBe(1)
        ->and($result['next_page_token'])->toBe('');

    expect(ZoomSmsConsent::query()->where('phone_number', '+12125551111')->first()?->status)
        ->toBe(SmsConsentStatus::OptedIn);

    expect(ZoomSmsConsent::query()->where('phone_number', '+12125552222')->first()?->status)
        ->toBe(SmsConsentStatus::OptedOut);
});
