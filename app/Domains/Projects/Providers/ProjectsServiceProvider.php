<?php

namespace App\Domains\Projects\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Permissions\ProjectPermissions;
use App\Domains\Projects\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ProjectsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        Gate::policy(Project::class, ProjectPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'projects');

        Livewire::component('app.domains.projects.livewire.admin.projects', \App\Domains\Projects\Livewire\Admin\Projects\Index::class);
        Livewire::component('app.domains.projects.livewire.admin.projects.form', \App\Domains\Projects\Livewire\Admin\Projects\Form::class);

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerPermissions(PermissionRegistry $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, ProjectPermissions::all()));
    }
}
