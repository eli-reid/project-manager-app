<?php

use App\Core\Zoom\Exceptions\ZoomSmsException;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use App\Core\Zoom\Services\ZoomSmsService;
use App\Core\Zoom\Services\ZoomTokenService;
use Illuminate\Support\Facades\Http;

it('normalizes sender and recipient numbers to +1 E.164 for sms sends', function (): void {
    config([
        'services.zoom.account_id' => 'account-123',
        'services.zoom.client_id' => 'client-123',
        'services.zoom.client_secret' => 'secret-123',
        'services.zoom.from_number' => '555-666-7777',
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
        'services.zoom.timeout' => 15,
    ]);

    app()->forgetInstance(ZoomTokenService::class);
    $tokenService = Mockery::mock(ZoomTokenService::class);
    $tokenService->shouldReceive('accessToken')->once()->andReturn('token-123');
    app()->instance(ZoomTokenService::class, $tokenService);

    app()->forgetInstance(ZoomSmsConsentService::class);
    $consentService = Mockery::mock(ZoomSmsConsentService::class);
    app()->instance(ZoomSmsConsentService::class, $consentService);

    Http::fake([
        '*phone/sms/messages' => Http::response([
            'message_id' => 'msg-123',
            'session_id' => 'sess-123',
        ], 200),
    ]);

    app()->forgetInstance(ZoomSmsService::class);

    /** @var ZoomSmsService $service */
    $service = app(ZoomSmsService::class);

    $result = $service->sendRaw('2125551234', 'Test message');

    expect($result['message_id'])->toBe('msg-123')
        ->and($result['session_id'])->toBe('sess-123');

    Http::assertSent(function ($request): bool {
        $payload = $request->data();

        return ($payload['sender']['phone_number'] ?? null) === '+15556667777'
            && ($payload['to_members'][0]['phone_number'] ?? null) === '+12125551234';
    });
});

it('rejects malformed recipient phone numbers before calling zoom', function (): void {
    config([
        'services.zoom.account_id' => 'account-123',
        'services.zoom.client_id' => 'client-123',
        'services.zoom.client_secret' => 'secret-123',
        'services.zoom.from_number' => '+15556667777',
        'services.zoom.api_base_url' => 'https://api.zoom.test/v2',
    ]);

    app()->forgetInstance(ZoomSmsService::class);

    /** @var ZoomSmsService $service */
    $service = app(ZoomSmsService::class);

    expect(fn () => $service->sendRaw('55512', 'Test message'))
        ->toThrow(ZoomSmsException::class, 'Invalid phone number format for Zoom SMS');
});
