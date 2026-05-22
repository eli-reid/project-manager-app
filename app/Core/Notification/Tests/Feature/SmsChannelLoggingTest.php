<?php

use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Contracts\SmsServiceContract;
use App\Core\Zoom\Exceptions\ZoomSmsException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

it('logs zoom exception details when sms send fails', function (): void {
    Log::spy();

    $smsService = Mockery::mock(SmsServiceContract::class);
    $smsService->shouldReceive('isConfigured')->once()->andReturn(true);
    $smsService->shouldReceive('send')
        ->once()
        ->with('2125551212', 'Test message')
        ->andThrow(ZoomSmsException::apiRequestFailed('send', 401, 'Invalid access token'));

    $channel = new SmsChannel($smsService);

    $notification = new class extends Notification
    {
        public function toSms(object $notifiable): array
        {
            return [
                'to' => '2125551212',
                'message' => 'Test message',
            ];
        }
    };

    $channel->send((object) ['id' => 'user-1'], $notification);

    Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'SMS notification failed to send via Zoom.'
            && ($context['exception_class'] ?? null) === ZoomSmsException::class
            && ($context['is_zoom_exception'] ?? false) === true
            && ($context['to'] ?? null) === '2125551212';
    });
});

it('logs when sms notification is withheld by zoom consent flow', function (): void {
    Log::spy();

    $smsService = Mockery::mock(SmsServiceContract::class);
    $smsService->shouldReceive('isConfigured')->once()->andReturn(true);
    $smsService->shouldReceive('send')
        ->once()
        ->with('2125551212', 'Test message')
        ->andReturn([]);

    $channel = new SmsChannel($smsService);

    $notification = new class extends Notification
    {
        public function toSms(object $notifiable): array
        {
            return [
                'to' => '2125551212',
                'message' => 'Test message',
            ];
        }
    };

    $channel->send((object) ['id' => 'user-1'], $notification);

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'SMS notification was withheld by Zoom consent flow.'
            && ($context['to'] ?? null) === '2125551212';
    });
});
