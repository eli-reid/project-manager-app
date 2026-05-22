<?php

use App\Core\Identity\Models\User;
use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Models\ZoomSmsConsent;
use App\Core\Zoom\Services\ZoomSmsConsentService;

it('syncs a single phone number when phone option is provided', function (): void {
    $service = Mockery::mock(ZoomSmsConsentService::class);
    $service->shouldReceive('syncFromZoomWithResponse')
        ->once()
        ->with('2125551212')
        ->andReturn([
            'status' => SmsConsentStatus::OptedIn,
            'response_status' => 200,
            'response_json' => [
                'phone_number_campaign_opt_statuses' => [
                    [
                        'consumer_phone_number' => '12125551212',
                        'zoom_phone_user_number' => '15556667777',
                        'opt_status' => 'opt_in',
                    ],
                ],
            ],
            'response_body' => '{"phone_number_campaign_opt_statuses":[{"consumer_phone_number":"12125551212"}]}',
            'request_phone_number' => '+12125551212',
            'request_consumer_phone_number' => '12125551212',
            'request_zoom_phone_user_number' => '15556667777',
            'error' => null,
        ]);

    app()->instance(ZoomSmsConsentService::class, $service);

    $this->artisan('zoom:sms-consent-sync --phone=2125551212')
        ->expectsOutputToContain('Single-number Zoom consent sync complete.')
        ->expectsOutputToContain('Status: opt_in')
        ->expectsOutputToContain('Response status: 200')
        ->expectsOutputToContain('Full API response:')
        ->expectsOutputToContain('phone_number_campaign_opt_statuses')
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

it('falls back to per-number sync when campaign endpoint validation fails', function (): void {
    User::factory()->create(['phone' => '2125551212']);
    ZoomSmsConsent::query()->create([
        'phone_number' => '+12125551213',
        'status' => SmsConsentStatus::Pending,
    ]);

    $service = Mockery::mock(ZoomSmsConsentService::class);
    $service->shouldReceive('syncCampaignConsentStatuses')
        ->once()
        ->with(100, 10)
        ->andThrow(new RuntimeException('Zoom SMS campaign opt-status list failed (HTTP 400): {"code":300,"message":"Validation Failed.","errors":[{"field":"consumer_phone_number","message":"Missing field."},{"field":"zoom_phone_user_numbers","message":"Missing field."}]}'));
    $service->shouldReceive('syncFromZoom')
        ->once()
        ->with('2125551212')
        ->andReturn(SmsConsentStatus::OptedIn);
    $service->shouldReceive('syncFromZoom')
        ->once()
        ->with('+12125551213')
        ->andReturn(SmsConsentStatus::OptedOut);

    app()->instance(ZoomSmsConsentService::class, $service);

    $this->artisan('zoom:sms-consent-sync')
        ->expectsOutputToContain('Campaign-wide endpoint is not available for this account/tenant.')
        ->expectsOutputToContain('Falling back to per-number consent sync using known local phone numbers...')
        ->expectsOutputToContain('Processed: 2')
        ->expectsOutputToContain('Opted in: 1')
        ->expectsOutputToContain('Opted out: 1')
        ->assertSuccessful();
});
