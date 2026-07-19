<?php

namespace App\PlugIns\Zoom\Providers;

use App\Core\Notification\Contracts\SmsServiceContract;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\PlugIns\Zoom\Channels\ZoomSms;
use App\PlugIns\Zoom\Data\ZoomConfig;
use App\PlugIns\Zoom\Services\ZoomSmsConsentService;
use App\PlugIns\Zoom\Services\ZoomSmsService;
use App\PlugIns\Zoom\Services\ZoomTokenService;
use Illuminate\Support\ServiceProvider;

class ZoomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ZoomConfig::class);
        $this->app->singleton(ZoomTokenService::class);
        $this->app->singleton(ZoomSmsConsentService::class);
        $this->app->singleton(ZoomSmsService::class);
        $this->app->singleton(SmsServiceContract::class, ZoomSmsService::class);
        $this->app->singleton(ZoomSms::class);
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerChannels();
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    private function registerChannels(): void
    {
        $this->app->afterResolving(NotificationChannelRegistry::class, function (NotificationChannelRegistry $registry): void {
            $registry->register('sms', ZoomSms::class);
        });

        if ($this->app->resolved(NotificationChannelRegistry::class)) {
            $this->app->make(NotificationChannelRegistry::class)->register('sms', ZoomSms::class);
        }
    }
}
