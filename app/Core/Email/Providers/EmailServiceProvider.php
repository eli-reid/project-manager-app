<?php

namespace App\Core\Email\Providers;

use App\Core\Email\Channels\MailChannel;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
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

        // Register the mail channel implementation by name so domains/plugins
        // can refer to it as 'mail'. The concrete class is resolved lazily.
        if ($app->bound(NotificationChannelRegistry::class)) {
            $channels = $app->make(NotificationChannelRegistry::class);
            $channels->register('mail', MailChannel::class);
        }

        // Optionally register a generic email notification definition. Specific
        // domains should register their own notification keys instead.
        if ($app->bound(NotificationRegistry::class)) {
            $registry = $app->make(NotificationRegistry::class);
            $registry->registerDefinitions([
                [
                    'key' => 'email.notification',
                    'label' => 'Email Notification',
                    'description' => 'Generic email notification placeholder',
                ],
            ]);
        }
    }
}
