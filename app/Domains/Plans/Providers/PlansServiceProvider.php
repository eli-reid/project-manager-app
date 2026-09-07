<?php

declare(strict_types=1);

namespace App\Domains\Plans\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Plans\Console\Commands\CheckRasterizerCommand;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Permissions\PlanPermissions;
use App\Domains\Plans\Services\PlanRasterizerManager;
use App\Domains\Plans\Services\Rasterizers\NullRasterizer;
use Illuminate\Support\ServiceProvider;

final class PlansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlanRasterizerManager::class);
        $this->app->bind(PlanRasterizerContract::class, function ($app): PlanRasterizerContract {
            if ($app->environment('testing')) {
                return new NullRasterizer;
            }

            return $app->make(PlanRasterizerManager::class)->driver();
        });
        $this->commands([CheckRasterizerCommand::class]);
    }

    public function boot(PermissionRegistryContract $permissionRegistry, SettingsRegistryContract $settingsRegistry): void
    {
        $permissionRegistry->registerPermissions(PlanPermissions::all());
        $settingsRegistry->registerConfigFile('plans', __DIR__.'/../config/settings.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
