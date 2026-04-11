<?php

namespace App\Domains\Projects\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Projects\Livewire\Admin\Projects\Form;
use App\Domains\Projects\Livewire\Admin\Projects\Index;
use App\Domains\Projects\Livewire\Admin\Projects\Show;
use App\Domains\Projects\Livewire\User\Projects\Index as UserProjectIndex;
use App\Domains\Projects\Livewire\User\Projects\Show as UserProjectShow;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Notifications\ProjectNotificationDefinitions;
use App\Domains\Projects\Permissions\ProjectPermissions;
use App\Domains\Projects\Policies\ProjectPolicy;
use App\Domains\Reports\Services\ReportRegistry;
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

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, SettingsRegistryContract $settingsRegistry, ReportRegistry $reportRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerReports($reportRegistry);
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
        Livewire::component('app.domains.projects.livewire.user.projects', UserProjectIndex::class);
        Livewire::component('app.domains.projects.livewire.user.projects.show', UserProjectShow::class);
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

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(ProjectPermissions::all());
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(ProjectNotificationDefinitions::definitions());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'operational.project-overview',
                'section' => 'operational',
                'title' => 'Project Status Overview',
                'description' => 'Review active projects and current execution status.',
                'route' => 'projects.index',
                'badge_label' => 'Operational',
                'badge_color' => 'sky',
                'sort' => 20,
            ],
        ]);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('projects', __DIR__.'/../config/settings.php');
    }
}
