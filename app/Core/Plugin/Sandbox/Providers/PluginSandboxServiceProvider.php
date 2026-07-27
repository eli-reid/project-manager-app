<?php

namespace App\Core\PluginSandbox\Providers;

use App\Core\PluginSandbox\Services\SandboxProfileRegistryService;
use Illuminate\Support\ServiceProvider;

class PluginSandboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SandboxProfileRegistryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
