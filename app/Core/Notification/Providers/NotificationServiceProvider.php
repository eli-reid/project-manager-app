<?php

namespace App\Core\Notification\Providers;

use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRegistry::class, fn (): NotificationRegistry => new NotificationRegistry);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-notification');
    }
}
