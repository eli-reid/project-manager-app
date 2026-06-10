<?php

namespace App\Domains\Assets\Providers;

use App\Domains\Assets\Contracts\AssetOrchestratorContract;
use App\Domains\Assets\Contracts\AssetSharingContract;
use App\Domains\Assets\Contracts\ProjectAssetLibraryContract;
use App\Domains\Assets\Services\AssetService;
use App\Domains\Assets\Services\AssetShareService;
use App\Domains\Assets\Services\ProjectAssetLibrary;
use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetOrchestratorContract::class, AssetService::class);
        $this->app->singleton(AssetSharingContract::class, AssetShareService::class);
        $this->app->singleton(ProjectAssetLibraryContract::class, ProjectAssetLibrary::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
