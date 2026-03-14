<?php

namespace App\Domains\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $domainProvidersPath = app_path('Domains');

        if (! File::isDirectory($domainProvidersPath)) {
            return;
        }

        $providerFiles = File::glob($domainProvidersPath.'/*/Providers/*ServiceProvider.php') ?: [];

        foreach ($providerFiles as $providerFile) {
            $relativePath = ltrim(Str::after((string) $providerFile, app_path()), '/\\');
            $providerClass = 'App\\'.Str::replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);

            if ($providerClass === self::class) {
                continue;
            }

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    public function boot(): void {}
}
