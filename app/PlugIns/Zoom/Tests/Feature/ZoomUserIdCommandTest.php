<?php

use App\Core\Settings\Facades\Settings;
use App\PlugIns\Zoom\Data\ZoomConfig;
use App\PlugIns\Zoom\Services\ZoomTokenService;
use Illuminate\Support\Facades\Http;

it('prints zoom user id from users me endpoint', function (): void {
    Settings::set('zoom.account_id', 'account-123');
    Settings::set('zoom.client_id', 'client-123');
    Settings::set('zoom.client_secret', 'secret-123');
    Settings::set('zoom.api_base_url', 'https://api.zoom.test/v2');
    Settings::set('zoom.timeout', '15');

    app()->forgetInstance(ZoomConfig::class);
    app()->forgetInstance(ZoomTokenService::class);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        'https://api.zoom.test/v2/users/me' => Http::response([
            'id' => 'u_abc123',
            'email' => 'owner@example.com',
        ], 200),
    ]);

    $this->artisan('zoom:user-id --json')
        ->expectsOutputToContain('Zoom users/me resolved successfully.')
        ->expectsOutputToContain('User ID: u_abc123')
        ->expectsOutputToContain('Email: owner@example.com')
        ->expectsOutputToContain('Response JSON:')
        ->expectsOutputToContain('"id": "u_abc123"')
        ->assertSuccessful();
});

it('prints api error details when users me request fails', function (): void {
    Settings::set('zoom.account_id', 'account-123');
    Settings::set('zoom.client_id', 'client-123');
    Settings::set('zoom.client_secret', 'secret-123');
    Settings::set('zoom.api_base_url', 'https://api.zoom.test/v2');
    Settings::set('zoom.timeout', '15');

    app()->forgetInstance(ZoomConfig::class);
    app()->forgetInstance(ZoomTokenService::class);

    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    Http::fake([
        'https://api.zoom.test/v2/users/me' => Http::response([
            'code' => 1001,
            'message' => 'User does not exist',
        ], 404),
    ]);

    $this->artisan('zoom:user-id')
        ->expectsOutputToContain('Zoom users/me request failed with status 404.')
        ->expectsOutputToContain('User does not exist')
        ->assertFailed();
});
