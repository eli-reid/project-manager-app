<?php

use App\PlugIns\Zoom\Services\ZoomSmsService;

it('sends a raw zoom sms using interactive prompts', function (): void {
    $service = Mockery::mock(ZoomSmsService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldReceive('sendRaw')
        ->once()
        ->with('+12125551234', 'Zoom SMS test from Laravel')
        ->andReturn([
            'message_id' => 'msg-123',
            'session_id' => 'sess-456',
        ]);

    app()->bind(ZoomSmsService::class, function () use ($service): ZoomSmsService {
        return $service;
    });

    $this->artisan('zoom:sms-test')
        ->expectsQuestion('Zoom Account ID', 'account-123')
        ->expectsQuestion('Zoom Client ID', 'client-123')
        ->expectsQuestion('Zoom Client Secret', 'secret-123')
        ->expectsQuestion('Zoom SMS From Number (E.164, e.g. +12125551234)', '+15556667777')
        ->expectsQuestion('Recipient phone number (E.164, e.g. +12125551234)', '+12125551234')
        ->expectsQuestion('Zoom user ID for consent lookups', 'me')
        ->expectsQuestion('Zoom SMS Campaign ID for consent sync (optional)', '')
        ->expectsQuestion('Message to send', 'Zoom SMS test from Laravel')
        ->expectsConfirmation('Bypass the consent gate and send a raw SMS?', 'yes')
        ->expectsOutputToContain('Sending raw SMS...')
        ->expectsOutputToContain('Zoom SMS send succeeded.')
        ->expectsOutputToContain('Message ID: msg-123')
        ->expectsOutputToContain('Session ID: sess-456')
        ->assertSuccessful();
});
