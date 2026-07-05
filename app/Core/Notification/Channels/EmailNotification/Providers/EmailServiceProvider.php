<?php
/**
 * Email service provider for the notification system.
 */


namespace App\Core\Notification\Channels\EmailNotification\Providers;

use App\Core\Notification\Channels\EmailNotification\Channels\MailChannel;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the email notification channel.
 *
 * Registers the mail channel and optionally a generic email notification definition.
 */

class EmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $app = $this->app;

        if ($app->bound(NotificationChannelRegistry::class)) {
            $channels = $app->make(NotificationChannelRegistry::class);
            $channels->register('mail', MailChannel::class);
        }
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
