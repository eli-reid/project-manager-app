<?php

namespace App\Core\Auth\Role\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Auth\Role\Livewire\Admin\Roles\Form;
use App\Core\Auth\Role\Livewire\Admin\Roles\Index;
use App\Core\Auth\Role\Livewire\Admin\Roles\Users;
use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\Role\Policies\RolePolicy;
use App\Core\User\Permissions\RolePermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerUIComponents();
        $this->registerRoutes();
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
        Livewire::component('app.core.user.livewire.admin.roles', Index::class);
        Livewire::component('app.core.user.livewire.admin.roles.form', Form::class);
        Livewire::component('app.core.user.livewire.admin.roles.users', Users::class);
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
