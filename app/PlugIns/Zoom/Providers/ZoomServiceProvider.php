<?php

namespace App\PlugIns\Zoom\Providers;

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
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
