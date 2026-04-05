<?php

namespace App\Domains\Projects\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
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

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'projects');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.projects.livewire.admin.projects', Index::class);
        Livewire::component('app.domains.projects.livewire.admin.projects.form', Form::class);
        Livewire::component('app.domains.projects.livewire.admin.projects.show', Show::class);
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
        $permissionRegistry->registerPermissions(ProjectPermissions::all());
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(ProjectNotificationDefinitions::definitions());
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('projects', __DIR__.'/../config/settings.php');
    }
}
