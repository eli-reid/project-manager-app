<?php

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NotificationDispatcherTest extends TestCase
{
    public function testDispatchToRegisteredChannel(): void
    {
        $channelRegistry = new NotificationChannelRegistry();
        $notificationRegistry = new NotificationRegistry();

        // simple fake channel
        $fake = new class implements NotificationChannel {
            public function convert($message): ChannelMessage
            {
                return new class(['content' => $message->body]) extends ChannelMessage {
                    public function channelName(): string { return 'fake'; }
                };
            }

            public function send(ChannelMessage $message): ChannelResult
            {
                return ChannelResult::success('fake', 'ext-1', ['message' => 'ok']);
            }
        };

        $channelRegistry->register('fake', fn () => $fake);

        $dispatcher = new NotificationDispatcher($channelRegistry, $notificationRegistry, new NullLogger());

        $msg = new NotificationMessage('test.event', 't', 'b', [], ['user:1']);

        $results = $dispatcher->dispatch($msg, ['fake']);

        $this->assertArrayHasKey('fake', $results);
        $this->assertTrue($results['fake']->success);
    }
}
