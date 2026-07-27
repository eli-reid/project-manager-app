<?php

namespace App\Core\PluginMarketplace\Providers;

use App\Core\PluginMarketplace\Services\MarketplacePluginCatalog;
use Illuminate\Support\ServiceProvider;

class PluginMarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarketplacePluginCatalog::class);
    }
}
