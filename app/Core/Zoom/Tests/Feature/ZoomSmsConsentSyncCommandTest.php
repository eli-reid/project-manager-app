<?php

use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Services\ZoomSmsConsentService;

it('syncs a single phone number when phone option is provided', function (): void {
    $service = Mockery::mock(ZoomSmsConsentService::class);
    $service->shouldReceive('syncFromZoom')
        ->once()
        ->with('2125551212')
        ->andReturn(SmsConsentStatus::OptedIn);

    app()->instance(ZoomSmsConsentService::class, $service);

    $this->artisan('zoom:sms-consent-sync --phone=2125551212')
        ->expectsOutputToContain('Single-number Zoom consent sync complete.')
        ->expectsOutputToContain('Status: opt_in')
        ->assertSuccessful();
});

it('syncs campaign consent statuses when no phone option is provided', function (): void {
    $service = Mockery::mock(ZoomSmsConsentService::class);
    $service->shouldReceive('syncCampaignConsentStatuses')
        ->once()
        ->with(50, 3)
        ->andReturn([
            'processed' => 12,
            'opted_in' => 10,
            'opted_out' => 2,
            'unknown' => 1,
            'next_page_token' => '',
        ]);

    app()->instance(ZoomSmsConsentService::class, $service);

    $this->artisan('zoom:sms-consent-sync --page-size=50 --max-pages=3')
        ->expectsOutputToContain('Starting campaign-wide Zoom consent sync...')
        ->expectsOutputToContain('Campaign-wide Zoom consent sync complete.')
        ->expectsOutputToContain('Processed: 12')
        ->expectsOutputToContain('Opted in: 10')
        ->expectsOutputToContain('Opted out: 2')
        ->expectsOutputToContain('Unknown/skipped: 1')
        ->assertSuccessful();
});
