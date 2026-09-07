<?php

declare(strict_types=1);

namespace App\Core\Assets\Providers;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\Contracts\FilePathNormalizerContract;
use App\Core\Assets\Contracts\FileStorageContract;
use App\Core\Assets\Services\AssetGatekeeper;
use App\Core\Assets\Services\AssetReferencerRegistry;
use App\Core\Assets\Services\AssetService;
use App\Core\Assets\Services\DefaultFilePathNormalizer;
use App\Core\Assets\Services\LaravelFileStorage;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetReferencerRegistry::class);
        $this->app->singleton(FileStorageContract::class, LaravelFileStorage::class);
        $this->app->singleton(FilePathNormalizerContract::class, DefaultFilePathNormalizer::class);
        $this->app->singleton(AssetOrchestratorContract::class, AssetService::class);
        $this->app->singleton(AssetGatekeeper::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerInfrastructure();
        $this->registerRoutes();
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('assets', __DIR__.'/../config/settings.php');
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'assets');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Livewire::addNamespace('assets', classNamespace: 'App\Core\Assets\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');
    }
}
