<?php

namespace App\Core\Audit\Providers;

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Audit\Permissions\AuditPermissions;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, fn (): AuditLogger => new AuditLogger);
        $this->app->alias(AuditLogger::class, AuditLoggerContract::class);
    }

    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerInfrastructure();
        $this->registerPermissions($permissionRegistry);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(AuditPermissions::all());
    }
}
