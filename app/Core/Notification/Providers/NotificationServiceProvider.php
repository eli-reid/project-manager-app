<?php

namespace App\Core\Notification\Providers;

use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Services\SettingsRegistry;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRegistry::class, fn (): NotificationRegistry => new NotificationRegistry);
    }

    public function boot(SettingsRegistry $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerInfrastructure();
    }

    private function registerSettings(SettingsRegistry $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('notifications', __DIR__.'/../config/settings.php');
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-notification');
    }
}
