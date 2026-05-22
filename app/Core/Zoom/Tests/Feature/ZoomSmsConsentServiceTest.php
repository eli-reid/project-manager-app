<?php

use App\Core\Zoom\Exceptions\ZoomSmsException;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use App\Core\Zoom\Services\ZoomTokenService;
use Illuminate\Support\Facades\Http;

it('lists campaign phone number opt statuses from zoom', function (): void {
    config([
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
        'services.zoom.sms_campaign_id' => 'campaign-123',
        'services.zoom.timeout' => 15,
    ]);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        'https://api.zoom.test/v2/phone/sms_campaigns/campaign-123/phone_numbers/opt_status*' => Http::response([
            'phone_number_campaign_opt_statuses' => [
                [
                    'consumer_phone_number' => '12125550001',
                    'zoom_phone_user_number' => '15556667777',
                    'opt_status' => 'opt_in',
                ],
            ],
            'next_page_token' => 'next-token-1',
        ], 200),
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    $result = $service->listCampaignPhoneNumberOptStatuses(pageSize: 25, nextPageToken: 'cursor-1');

    expect($result['phone_number_campaign_opt_statuses'])->toHaveCount(1)
        ->and($result['phone_number_campaign_opt_statuses'][0]['opt_status'])->toBe('opt_in')
        ->and($result['next_page_token'])->toBe('next-token-1');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.zoom.test/v2/phone/sms_campaigns/campaign-123/phone_numbers/opt_status?page_size=25&next_page_token=cursor-1'
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

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        'https://api.zoom.test/v2/phone/sms_campaigns/campaign-123/phone_numbers/opt_status*' => Http::response([], 429),
    ]);

    app()->forgetInstance(ZoomSmsConsentService::class);

    /** @var ZoomSmsConsentService $service */
    $service = app(ZoomSmsConsentService::class);

    expect(fn () => $service->listCampaignPhoneNumberOptStatuses())
        ->toThrow(ZoomSmsException::class, 'rate limit exceeded');
});
