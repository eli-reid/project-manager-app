<?php

use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use Psr\Log\NullLogger;

it('executes the notification:test command and returns results', function () {
    $fakeResult = ChannelResult::success('email', null, ['message' => 'ok']);

    // Create a real dispatcher with minimal dependencies; channels can be empty for this test.
    $channelRegistry = new NotificationChannelRegistry();
    $notificationRegistry = new NotificationRegistry();

    $realDispatcher = new NotificationDispatcher($channelRegistry, $notificationRegistry, new NullLogger());

    // Bind our real dispatcher instance
    $this->app->instance(NotificationDispatcher::class, $realDispatcher);

    $this->artisan('notification:test', ['type' => 'test', '--channels' => 'email', '--recipients' => 'user:1', '--title' => 'Hi', '--body' => 'Hello'])
        ->assertExitCode(0);
});
