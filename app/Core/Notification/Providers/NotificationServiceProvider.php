<?php

namespace App\Core\Notification\Providers;

use App\Core\Notification\Commands\SetupVapidKeysCommand;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationDispatcher;
use Psr\Log\LoggerInterface;
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

        // Registry for available channel implementations (plugins/domains register into this)
        $this->app->singleton(NotificationChannelRegistry::class, fn (): NotificationChannelRegistry => new NotificationChannelRegistry());

        // Dispatcher uses the channel registry and notification registry; logger is injected by the container
        $this->app->singleton(NotificationDispatcher::class, function ($app): NotificationDispatcher {
            return new NotificationDispatcher(
                $app->make(NotificationChannelRegistry::class),
                $app->make(NotificationRegistry::class),
                $app->make(LoggerInterface::class)
            );
        });


    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerInfrastructure();
        $this->registerCommands();
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
