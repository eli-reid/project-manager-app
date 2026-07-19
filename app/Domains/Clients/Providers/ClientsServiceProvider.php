<?php

namespace App\Domains\Clients\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\UI\Navigation\Services\NavigationManager;
use App\Domains\Clients\Models\Client;
use App\Domains\Clients\Permissions\ClientPermissions;
use App\Domains\Clients\Policies\ClientPolicy;
use App\Providers\Concerns\RegistersNavigationItems;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ClientsServiceProvider extends ServiceProvider
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
        Gate::policy(Client::class, ClientPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'clients');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('clients', classNamespace: 'App\Domains\Clients\Livewire');
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
        $permissionRegistry->registerPermissions(ClientPermissions::all());
    }

    private function registerNavigation(NavigationManager $navigationManager): void
    {
        $this->registerAdminNavigationItem($navigationManager, 'admin-clients', 'Clients', 'admin.clients.index', 'building-2', 20, [$this->policyPermission('viewAny', Client::class)]);
    }
}
