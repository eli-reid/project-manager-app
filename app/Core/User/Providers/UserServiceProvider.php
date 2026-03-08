<?php

namespace App\Core\User\Providers;

use App\Core\User\Models\User;
use App\Core\User\Permissions\PermissionPermissions;
use App\Core\User\Permissions\RolePermissions;
use App\Core\User\Permissions\UserPermissions;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class, function () {
            return new PermissionRegistry;
        });

        $this->app->singleton(DomainPermissionSynchronizer::class, function () {
            return new DomainPermissionSynchronizer;
        });
    }

    public function boot(): void
    {
        $this->registerCorePermissions();
        $this->app->booted(function (): void {
            $this->syncRegisteredPermissions();
        });
        $this->registerAuthorizationGates();
        $this->configureRoutes();
    }

    private function registerCorePermissions(): void
    {
        /** @var PermissionRegistry $registry */
        $registry = $this->app->make(PermissionRegistry::class);

        $permissionDefinitions = [
            ...UserPermissions::all(),
            ...RolePermissions::all(),
            ...PermissionPermissions::all(),
        ];

        $registry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, $permissionDefinitions));
    }

    private function syncRegisteredPermissions(): void
    {
        /** @var DomainPermissionSynchronizer $synchronizer */
        $synchronizer = $this->app->make(DomainPermissionSynchronizer::class);
        $synchronizer->syncIfChanged();
    }

    private function registerAuthorizationGates(): void
    {
        Gate::define('admin', function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('access-admin', function (User $user): bool {
            return $user->isAdmin();
        });
    }

    private function configureRoutes(): void
    {
        $adminRouteRegistrar = function (): void {
            require __DIR__.'/../Routes/users/admin.php';
            require __DIR__.'/../Routes/roles/admin.php';
        };

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:access-admin'])
            ->group($adminRouteRegistrar);

        /**Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__ . '/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__ . '/../Routes/api.php');**/
    }
}
