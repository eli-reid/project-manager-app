<?php

use App\PlugIns\Zoom\Services\ZoomSmsConsentService;

it('lists campaign phone number opt statuses using interactive prompts', function (): void {
    $service = Mockery::mock(ZoomSmsConsentService::class);
    $service->shouldReceive('listCampaignPhoneNumberOptStatuses')
        ->once()
        ->with(30, null)
        ->andReturn([
            'phone_number_campaign_opt_statuses' => [
                [
                    'consumer_phone_number' => '12125550001',
                    'zoom_phone_user_number' => '15556667777',
                    'opt_status' => 'opt_in',
                    'opt_in_status' => 1,
                ],
            ],
            'next_page_token' => 'next-token-1',
        ]);

    app()->bind(ZoomSmsConsentService::class, function () use ($service): ZoomSmsConsentService {
        return $service;
    });

    $this->artisan('zoom:sms-campaign-list')
        ->expectsQuestion('Zoom Account ID', 'account-123')
        ->expectsQuestion('Zoom Client ID', 'client-123')
        ->expectsQuestion('Zoom Client Secret', 'secret-123')
        ->expectsQuestion('Zoom SMS Campaign ID', 'campaign-123')
        ->expectsQuestion('Page size', '30')
        ->expectsQuestion('Next page token (optional)', '')
        ->expectsOutputToContain('Fetching campaign phone number opt statuses...')
        ->expectsOutputToContain('12125550001')
        ->expectsOutputToContain('opt_in')
        ->expectsOutputToContain('Next page token: next-token-1')
        ->assertSuccessful();
});
