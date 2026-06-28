<?php

namespace App\Domains\PushNotification\Providers;

use Illuminate\Support\ServiceProvider;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use App\Domains\PushNotification\Channels\WebPushChannel;

class PushNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // nothing to bind at register time for now
    }

    public function boot(): void
    {
        $app = $this->app;

        // Register the push channel implementation into the global channel registry
        if ($app->bound(NotificationChannelRegistry::class)) {
            $channels = $app->make(NotificationChannelRegistry::class);
            // Register by name 'webpush' — domain/plugin installers can change this
            $channels->register('webpush', WebPushChannel::class);
        }

        // Register notification definitions (keys) into the NotificationRegistry
        if ($app->bound(NotificationRegistry::class)) {
            $registry = $app->make(NotificationRegistry::class);

            $registry->registerDefinitions([
                [
                    'key' => 'push.notification',
                    'label' => 'Push Notification',
                    'description' => 'Generic browser push notification',
                ],
            ]);
        }
    }
}
