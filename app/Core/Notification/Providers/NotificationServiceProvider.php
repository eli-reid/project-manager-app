<?php

namespace App\Core\Notification\Providers;

use App\Core\Notification\Commands\SetupVapidKeysCommand;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Core\Notification\Contracts\SmsServiceContract;
use App\Core\Notification\Services\NullSmsService;
use App\Core\Notification\Contracts\EmailServiceContract;
use App\Core\Notification\Services\NullEmailService;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRegistry::class, fn (): NotificationRegistry => new NotificationRegistry);

        // Default SMS service binding (no-op). Plugins should rebind this.
        $this->app->bind(SmsServiceContract::class, NullSmsService::class);
        $this->app->bind(EmailServiceContract::class, NullEmailService::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerInfrastructure();
        $this->registerCommands();
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('notifications', __DIR__.'/../config/settings.php');
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-notification');
        Livewire::addNamespace('core.notification', classNamespace: 'App\Core\Notification\Livewire');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SetupVapidKeysCommand::class,
        ]);
    }
}
