<?php

namespace App\Core\User\Providers;

use App\Core\User\Contracts\PermissionRegistryContract;
use App\Core\User\Livewire\Admin\Roles\Form;
use App\Core\User\Livewire\Admin\Roles\Index;
use App\Core\User\Livewire\Admin\Roles\Users;
use App\Core\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\User\Livewire\Admin\Users\Index as UserIndex;
use App\Core\User\Livewire\Settings\Appearance;
use App\Core\User\Livewire\Settings\DeleteUserForm;
use App\Core\User\Livewire\Settings\Password;
use App\Core\User\Livewire\Settings\Profile;
use App\Core\User\Livewire\Settings\TwoFactor;
use App\Core\User\Livewire\Settings\TwoFactor\RecoveryCodes;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Observers\UserObserver;
use App\Core\User\Permissions\FoundationPermissions;
use App\Core\User\Permissions\RolePermissions;
use App\Core\User\Permissions\UserPermissions;
use App\Core\User\Policies\RolePolicy;
use App\Core\User\Policies\UserPolicy;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PermissionRegistryContract::class, PermissionRegistry::class);
        $this->app->singleton(DomainPermissionSynchronizer::class);
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerPermissions();
        $this->app->booted(function (): void {
            $this->syncRegisteredPermissions();
        });
        $this->registerAuthorization();
        $this->registerObservers();
        $this->registerRoutes();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-user');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.core.user.livewire.admin.roles', Index::class);
        Livewire::component('app.core.user.livewire.admin.roles.form', Form::class);
        Livewire::component('app.core.user.livewire.admin.roles.users', Users::class);
        Livewire::component('app.core.user.livewire.admin.users', UserIndex::class);
        Livewire::component('app.core.user.livewire.admin.users.form', UserForm::class);
        Livewire::component('settings.profile', Profile::class);
        Livewire::component('settings.password', Password::class);
        Livewire::component('settings.appearance', Appearance::class);
        Livewire::component('settings.two-factor', TwoFactor::class);
        Livewire::component('settings.delete-user-form', DeleteUserForm::class);
        Livewire::component('settings.two-factor.recovery-codes', RecoveryCodes::class);
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);

        $permissionDefinitions = [
            ...UserPermissions::all(),
            ...RolePermissions::all(),
            ...FoundationPermissions::all(),
        ];

        $registry->registerPermissions($permissionDefinitions);
    }

    private function syncRegisteredPermissions(): void
    {
        /** @var DomainPermissionSynchronizer $synchronizer */
        $synchronizer = $this->app->make(DomainPermissionSynchronizer::class);
        $synchronizer->syncIfChanged();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::define('admin', function (User $user): bool {
            return $user->isAdmin();
        });
    }

    private function registerObservers(): void
    {
        User::observe(UserObserver::class);
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(function (): void {
                Route::middleware('can:viewAny,'.User::class)
                    ->group(__DIR__.'/../Routes/users/admin.php');

                Route::middleware('can:viewAny,'.Role::class)
                    ->group(__DIR__.'/../Routes/roles/admin.php');
            });

        /**Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__ . '/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__ . '/../Routes/api.php');**/
    }
}
