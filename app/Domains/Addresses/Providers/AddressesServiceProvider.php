<?php

namespace App\Domains\Addresses\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\UI\Navigation\Services\NavigationManager;
use App\Domains\Addresses\Models\Address;
use App\Domains\Addresses\Permissions\AddressPermissions;
use App\Domains\Addresses\Policies\AddressPolicy;
use App\Providers\Concerns\RegistersNavigationItems;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AddressesServiceProvider extends ServiceProvider
{
    use RegistersNavigationItems;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NavigationManager $navigationManager): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNavigation($navigationManager);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Address::class, AddressPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'addresses');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('addresses', classNamespace: 'App\Domains\Addresses\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(AddressPermissions::all());
    }

    private function registerNavigation(NavigationManager $navigationManager): void
    {
        $this->registerAdminNavigationItem($navigationManager, 'admin-addresses', 'Addresses', 'admin.addresses.index', 'map-pin', 25, [$this->policyPermission('viewAny', Address::class)]);
    }
}
