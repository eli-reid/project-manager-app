<?php

namespace App\Domains\Projects\Providers;

use App\Core\Notification\Services\NotificationRegistry;
use App\Core\User\Services\PermissionRegistry;
use App\Domains\Projects\Livewire\Admin\Projects\Form;
use App\Domains\Projects\Livewire\Admin\Projects\Index;
use App\Domains\Projects\Livewire\Admin\Projects\Show;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Notifications\ProjectNotificationDefinitions;
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

    public function boot(PermissionRegistry $permissionRegistry, NotificationRegistry $notificationRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        Gate::policy(Project::class, ProjectPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'projects');

        Livewire::component('app.domains.projects.livewire.admin.projects', Index::class);
        Livewire::component('app.domains.projects.livewire.admin.projects.form', Form::class);
        Livewire::component('app.domains.projects.livewire.admin.projects.show', Show::class);

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

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(ProjectNotificationDefinitions::definitions());
    }
}
