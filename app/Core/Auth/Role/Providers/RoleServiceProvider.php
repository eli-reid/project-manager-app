<?php

namespace App\Core\Auth\Role\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\Role\Policies\RolePolicy;
use App\Core\Identity\Permissions\RolePermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'auth-role');
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }

    private function registerPermissions(): void
    {
        if (! $this->app->bound(PermissionRegistryContract::class)) {
            return;
        }

        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);
        $registry->registerPermissions(RolePermissions::all());
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('roles', classNamespace: 'App\Core\Auth\Role\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(function (): void {
                Route::middleware('can:viewAny,'.Role::class)
                    ->group(__DIR__.'/../Routes/roles/admin.php');
            });
    }
}
