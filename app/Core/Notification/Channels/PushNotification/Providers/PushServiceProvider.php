<?php

namespace App\Core\Notification\Channels\PushNotification\Providers;

use App\Core\Notification\Channels\PushNotification\Channels\PushChannel;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\WebPushChannel as ExternalWebPushChannel;

class PushServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally left minimal. If EmailChannel requires constructor
        // dependencies, bind the implementation here so the container can
        // resolve it when the channel registry instantiates it.
    }

    public function boot(): void
    {
        $app = $this->app;
        // Prefer the external WebPush channel when the package is available.
        if ($app->bound(NotificationChannelRegistry::class)) {
            $channels = $app->make(NotificationChannelRegistry::class);

            if (class_exists(ExternalWebPushChannel::class)) {
                $channels->register('push', ExternalWebPushChannel::class);
            } else {
                // Fallback to local PushChannel implementation (safe placeholder)
                $channels->register('push', PushChannel::class);
            }
        }

        // Providers may also register generic placeholder definitions for push,
        // but specific domains should register their own notification keys.
        if ($app->bound(NotificationRegistry::class)) {
            $registry = $app->make(NotificationRegistry::class);
            $registry->registerDefinitions([
                [
                    'key' => 'push.notification',
                    'label' => 'Push Notification',
                    'description' => 'Push notification placeholder',
                ],
            ]);
        }
    }
}
