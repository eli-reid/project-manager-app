<?php

namespace App\Core\Assets\Providers;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\Contracts\AssetSharingContract;
use App\Core\Assets\Services\AssetService;
use App\Core\Assets\Services\AssetShareService;
use App\Core\Assets\Files\Contracts\FileStorageContract as FilesFileStorageContract;
use App\Core\Assets\Files\Contracts\FilePathNormalizerContract as FilesFilePathNormalizerContract;
use App\Core\Assets\Files\Services\LaravelFileStorage as FilesLaravelFileStorage;
use App\Core\Assets\Files\Services\DefaultFilePathNormalizer as FilesDefaultFilePathNormalizer;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Core\Assets\Livewire\AssetUpload;

class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetOrchestratorContract::class, AssetService::class);
        $this->app->singleton(AssetSharingContract::class, AssetShareService::class);

        // Bind Files infrastructure into Assets domain so plugins and other domains
        // that resolve these contracts will get the implementation registered here.
        $this->app->singleton(FilesFileStorageContract::class, FilesLaravelFileStorage::class);
        $this->app->singleton(FilesFilePathNormalizerContract::class, FilesDefaultFilePathNormalizer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'assets');
        Livewire::addNamespace('assets', classNamespace: 'App\\Core\\Assets\\Livewire');
        Livewire::component('assets::asset-upload', AssetUpload::class);
    }
}
