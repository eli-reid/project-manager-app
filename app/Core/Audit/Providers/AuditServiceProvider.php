<?php

namespace App\Core\Audit\Providers;

use App\Core\Audit\Permissions\AuditPermissions;
use App\Core\Audit\Services\AuditLogger;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, fn (): AuditLogger => new AuditLogger);
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerPermissions();
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    private function registerPermissions(): void
    {
        if (! $this->app->bound(PermissionRegistry::class)) {
            return;
        }

        /** @var PermissionRegistry $registry */
        $registry = $this->app->make(PermissionRegistry::class);

        $registry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, AuditPermissions::all()));
    }
}
