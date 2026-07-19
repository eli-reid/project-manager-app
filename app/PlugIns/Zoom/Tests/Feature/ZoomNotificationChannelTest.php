<?php

use App\Core\Notification\Contracts\SmsMessage;
use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationRegistry;
use App\PlugIns\Zoom\Channels\ZoomSms;
use App\PlugIns\Zoom\Exceptions\ZoomSmsException;
use App\PlugIns\Zoom\Providers\ZoomServiceProvider;
use App\PlugIns\Zoom\Services\ZoomSmsService;
use Psr\Log\NullLogger;

beforeEach(function (): void {
    (new ZoomServiceProvider(app()))->boot();
});

it('registers zoom as the sms channel in the notification registry', function (): void {
    expect(app(NotificationChannelRegistry::class)->has('sms'))->toBeTrue();
});

it('dispatches sms notifications through the zoom channel with bounded payload data', function (): void {
    $service = Mockery::mock(ZoomSmsService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldReceive('send')
        ->once()
        ->with('+12125551234', Mockery::on(function ($message): bool {
            return $message instanceof SmsMessage
                && $message->body() === 'Project update'
                && $message->title() === 'Status changed';
        }))
        ->andReturn([
            'message_id' => 'msg-123',
            'session_id' => 'sess-123',
        ]);

    app()->instance(ZoomSmsService::class, $service);
    app()->forgetInstance(ZoomSms::class);

    $dispatcher = new NotificationDispatcher(
        app(NotificationChannelRegistry::class),
        new NotificationRegistry,
        new NullLogger,
    );

    $result = $dispatcher->dispatch(new NotificationMessage(
        type: 'projects.status-changed',
        title: 'Status changed',
        body: 'Project update',
        data: ['project_id' => 'proj-123'],
        recipients: ['phone:+12125551234'],
        metadata: ['plugin' => 'zoom'],
    ), ['sms']);

    expect($result['sms']->success)->toBeTrue()
        ->and($result['sms']->externalId)->toBe('msg-123')
        ->and($result['sms']->recipientStatus)->toHaveKey('+12125551234')
        ->and($result['sms']->recipientStatus['+12125551234']['status'])->toBe('delivered');
});

it('returns a withheld result when zoom consent flow blocks delivery', function (): void {
    $service = Mockery::mock(ZoomSmsService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldReceive('send')->once()->andReturn([]);

    app()->instance(ZoomSmsService::class, $service);
    app()->forgetInstance(ZoomSms::class);

    $dispatcher = new NotificationDispatcher(
        app(NotificationChannelRegistry::class),
        new NotificationRegistry,
        new NullLogger,
    );

    $result = $dispatcher->dispatch(new NotificationMessage(
        type: 'projects.status-changed',
        body: 'Project update',
        recipients: ['phone:+12125551234'],
    ), ['sms']);

    expect($result['sms']->success)->toBeTrue()
        ->and($result['sms']->message)->toBe('withheld')
        ->and($result['sms']->recipientStatus['+12125551234']['status'])->toBe('withheld');
});

it('returns a failed result when zoom sms delivery throws an exception', function (): void {
    $service = Mockery::mock(ZoomSmsService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldReceive('send')
        ->once()
        ->andThrow(ZoomSmsException::apiRequestFailed('send', 401, 'Invalid access token'));

    app()->instance(ZoomSmsService::class, $service);
    app()->forgetInstance(ZoomSms::class);

    $dispatcher = new NotificationDispatcher(
        app(NotificationChannelRegistry::class),
        new NotificationRegistry,
        new NullLogger,
    );

    $result = $dispatcher->dispatch(new NotificationMessage(
        type: 'projects.status-changed',
        body: 'Project update',
        recipients: ['phone:+12125551234'],
    ), ['sms']);

    expect($result['sms']->success)->toBeFalse()
        ->and($result['sms']->statusCode)->toBe(401)
        ->and($result['sms']->recipientStatus['+12125551234']['status'])->toBe('failed');
});
