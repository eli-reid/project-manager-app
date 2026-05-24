<?php

namespace App\Core\Auth\User\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Auth\User\Observers\UserObserver;
use App\Core\Auth\User\Policies\UserPolicy;
use App\Core\Identity\Models\User;
use App\Core\Identity\Permissions\UserPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AuthUserServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerInfrastructure();
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerObservers();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'auth-user');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(UserPermissions::all());
    }

    private function registerAuthorization(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('admin', function (User $user): bool {
            return $user->isAdmin();
        });
    }

    private function registerObservers(): void
    {
        User::observe(UserObserver::class);
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('users', classNamespace: 'App\Core\Auth\User\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(function (): void {
                Route::middleware('can:viewAny,'.User::class)
                    ->group(__DIR__.'/../Routes/users/admin.php');
            });
    }
}
