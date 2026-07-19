<?php

namespace App\Core\Auth\Role\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\Role\Policies\RolePolicy;
use App\Core\Identity\Permissions\RolePermissions;
use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class RoleServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistryContract $permissionRegistry, DashboardPanelRegistry $panelRegistry): void
    {
        $this->registerInfrastructure();
        $this->registerAuthorization();
        $this->registerPermissions($permissionRegistry);
        $this->registerDashboardPanels($panelRegistry);
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

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(RolePermissions::all());
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('roles', classNamespace: 'App\Core\Auth\Role\Livewire');
    }

    private function registerDashboardPanels(DashboardPanelRegistry $panelRegistry): void
    {
        $panelRegistry->registerDefinitions([
            new PanelDefinition(
                key: 'access-roles',
                component: 'roles::admin.roles.index',
                icon: 'shield-check',
                sort: 111,
                ability: 'viewAny',
                abilityModel: Role::class,
                label: 'Roles',
                description: 'Define and manage role access controls.',
                navigationSectionKey: 'administration',
                navigationSectionLabel: 'Administration',
                navigationSectionOrder: 30,
                registerInNavigation: false,
            ),
        ]);
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
