<?php

namespace App\Core\Assets\Providers;

use App\Core\Assets\Contracts\AssetOrchestratorInterface;
use App\Core\Assets\Contracts\AssetSharingInterface;
use App\Core\Assets\Services\AssetService;
use App\Core\Assets\Services\AbstractAssetShare;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Core\Assets\Livewire\AssetUpload;

class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetOrchestratorInterface::class, AssetService::class);
        $this->app->singleton(AssetSharingInterface::class, AbstractAssetShare::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'assets');
        Livewire::addNamespace('assets', classNamespace: 'App\\Core\\Assets\\Livewire');
        Livewire::component('assets::asset-upload', AssetUpload::class);
    }
}
