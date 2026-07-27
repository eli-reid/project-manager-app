<?php

namespace App\Core\PluginExternalApi\Providers;

use App\Core\PluginExternalApi\Services\ExternalApiRegistryService;
use Illuminate\Support\ServiceProvider;

class PluginExternalApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExternalApiRegistryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
