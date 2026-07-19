<?php

namespace App\PlugIns\Providers;

use App\PlugIns\Contracts\PluginContextFactory;
use App\PlugIns\Contracts\PluginDataGateway;
use App\PlugIns\Contracts\PluginHost;
use App\PlugIns\Services\PluginContextFactoryService;
use App\PlugIns\Services\PluginDataGatewayService;
use App\PlugIns\Services\PluginDataRegistry;
use App\PlugIns\Services\PluginHostBridge;
use Illuminate\Support\ServiceProvider;

class PluginRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginDataRegistry::class);
        $this->app->singleton(PluginDataGateway::class, PluginDataGatewayService::class);
        $this->app->singleton(PluginContextFactory::class, PluginContextFactoryService::class);
        $this->app->singleton(PluginHost::class, PluginHostBridge::class);
    }
}
