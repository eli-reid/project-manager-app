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

    public function boot(PermissionRegistryContract $permissionRegistry, DomainPermissionSynchronizer $synchronizer): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->app->booted(fn () => $synchronizer->syncIfChanged());
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(FoundationPermissions::all());
    }
}
