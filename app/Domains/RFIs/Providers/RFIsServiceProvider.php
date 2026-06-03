<?php

namespace App\Domains\RFIs\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Permissions\RFIPermissions;
use App\Domains\RFIs\Policies\RFIPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class RFIsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(RFI::class, RFIPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'rfis');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('rfis', classNamespace: 'App\\Domains\\RFIs\\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(RFIPermissions::all());
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            [
                'key' => 'rfis',
                'label' => 'RFIs',
                'sort' => 80,
                'mode_param' => 'rfiMode',
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('rfis.view-any')
                    || $user->hasPermission('rfis.view')
                    || $user->hasPermission('rfis.create'),
            ],
        ]);
    }
}
