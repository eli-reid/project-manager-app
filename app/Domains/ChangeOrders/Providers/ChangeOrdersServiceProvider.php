<?php

namespace App\Domains\ChangeOrders\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\ChangeOrders\Permissions\ChangeOrderPermissions;
use App\Domains\ChangeOrders\Policies\ChangeOrderPolicy;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ChangeOrdersServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('change-orders.', 'change-orders.mobile.');

        $this->registerPermissions($permissionRegistry);
        $this->registerProjectTab($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(ChangeOrderPermissions::all());
    }

    private function registerProjectTab(ProjectTabRegistry $projectTabRegistry): void
    {

        projectTabPanel
        $projectTabRegistry->registerProvider(new ProjectTab(
            key: 'change-orders',
            label: 'Change Orders',
            sort: 120,
            badgeResolver: static fn (ProjectTab $tab, $project) => $project->changeOrders()->count(),
            visibilityResolver: static fn (ProjectTab $tab, $project, $user) => $user->can('viewAny', [ChangeOrder::class, $project]),

        ));
    }

    private function registerAuthorization(): void
    {
        Gate::policy(ChangeOrder::class, ChangeOrderPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'change-orders');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('change-orders', classNamespace: 'App\\Domains\\ChangeOrders\\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('api')
            ->name('api.')
            ->middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
