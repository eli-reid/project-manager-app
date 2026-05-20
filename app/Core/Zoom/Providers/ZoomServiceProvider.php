<?php

namespace App\Core\Zoom\Providers;

use App\Core\Zoom\Data\ZoomConfig;
use App\Core\Zoom\Services\ZoomSmsService;
use App\Core\Zoom\Services\ZoomTokenService;
use Illuminate\Support\ServiceProvider;

class ZoomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ZoomConfig::class);
        $this->app->singleton(ZoomTokenService::class);
        $this->app->singleton(ZoomSmsService::class);
    }
}
