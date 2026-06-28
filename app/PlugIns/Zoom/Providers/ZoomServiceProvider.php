<?php

namespace App\PlugIns\Zoom\Providers;

use App\Core\Notification\Contracts\SmsServiceContract;
use App\Core\Settings\Contracts\SettingsRegistryContract;
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
        $this->app->bind(SmsServiceContract::class, ZoomSmsService::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        self::registerInfrastructure();
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('zoom', __DIR__.'/../config/settings.php');
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
