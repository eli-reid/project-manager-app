<?php

namespace App\Core\Files\Providers;

use App\Core\Files\Contracts\FilePathNormalizerContract;
use App\Core\Files\Contracts\FileStorageContract;
use App\Core\Files\Services\DefaultFilePathNormalizer;
use App\Core\Files\Services\LaravelFileStorage;
use Illuminate\Support\ServiceProvider;

class FilesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileStorageContract::class, LaravelFileStorage::class);
        $this->app->singleton(FilePathNormalizerContract::class, DefaultFilePathNormalizer::class);
    }
}
