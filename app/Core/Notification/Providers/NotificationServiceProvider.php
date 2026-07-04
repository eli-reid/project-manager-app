<?php

namespace App\Core\Notification\Providers;

use App\Core\Notification\Channels\DatabaseChannelAdapter;
use App\Core\Notification\Channels\RegistryBridgeChannel;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRegistry::class, fn (): NotificationRegistry => new NotificationRegistry);

        // Registry for available channel implementations (plugins/domains register into this)
        $this->app->singleton(NotificationChannelRegistry::class, function (): NotificationChannelRegistry {
            $registry = new NotificationChannelRegistry;

            // Built-in adapters; plugins/domains can register more channels.
            $registry->register('database', DatabaseChannelAdapter::class);

            return $registry;
        });

        // Register Laravel channel bridge used by via() => [RegistryBridgeChannel::class]
        $this->app->singleton(RegistryBridgeChannel::class, function ($app): RegistryBridgeChannel {
            return new RegistryBridgeChannel(
                $app->make(NotificationDispatcher::class),
                $app->make(NotificationPreferenceService::class),
                $app->make(NotificationChannelRegistry::class),
                $app->make(LoggerInterface::class)
            );
        });

        // Dispatcher uses the channel registry and notification registry; logger is injected by the container
        $this->app->singleton(NotificationDispatcher::class, function ($app): NotificationDispatcher {
            return new NotificationDispatcher(
                $app->make(NotificationChannelRegistry::class),
                $app->make(NotificationRegistry::class),
                $app->make(LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-notification');
        Livewire::addNamespace('core.notification', classNamespace: 'App\Core\Notification\Livewire');
    }
}
