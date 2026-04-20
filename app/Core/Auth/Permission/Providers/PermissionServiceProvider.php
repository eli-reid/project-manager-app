<?php

namespace App\Core\Auth\Permission\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Permission\Services\PermissionRegistry;
use App\Core\Identity\Permissions\FoundationPermissions;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistryContract::class, PermissionRegistry::class);
        $this->app->singleton(DomainPermissionSynchronizer::class);
    }

    public function boot(): void
    {
        $this->registerPermissions();
        $this->app->booted(fn () => $this->syncRegisteredPermissions());
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);
        $registry->registerPermissions(FoundationPermissions::all());
    }

    private function syncRegisteredPermissions(): void
    {
        /** @var DomainPermissionSynchronizer $synchronizer */
        $synchronizer = $this->app->make(DomainPermissionSynchronizer::class);
        $synchronizer->syncIfChanged();
    }
}
