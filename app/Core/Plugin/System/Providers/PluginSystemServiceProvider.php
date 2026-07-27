<?php

namespace App\Core\PluginSystem\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\PluginSystem\Livewire\Admin\Plugins\Index;
use App\Core\PluginSystem\Models\InstalledPlugin;
use App\Core\PluginSystem\Permissions\PluginSystemPermissions;
use App\Core\PluginSystem\Policies\InstalledPluginPolicy;
use App\Core\PluginSystem\Services\PluginDiscoveryService;
use App\Core\PluginSystem\Services\PluginInstallService;
use App\Core\PluginSystem\Services\PluginSecurityReviewService;
use App\Core\PluginSystem\Services\SystemPluginCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PluginSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginDiscoveryService::class);
        $this->app->singleton(SystemPluginCatalog::class);
        $this->app->singleton(PluginSecurityReviewService::class);
        $this->app->singleton(PluginInstallService::class);
    }

    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerAuthorization();
        $this->registerPermissions($permissionRegistry);
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(InstalledPlugin::class, InstalledPluginPolicy::class);
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(PluginSystemPermissions::all());
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'plugins');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('plugins.admin.index', Index::class);
        Livewire::addNamespace('plugins', classNamespace: 'App\Core\PluginSystem\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }
}
