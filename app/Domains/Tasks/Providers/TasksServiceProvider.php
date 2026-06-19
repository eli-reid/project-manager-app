<?php

namespace App\Domains\Tasks\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyMetrics;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyTemplates;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use App\Domains\Tasks\Notifications\TaskNotificationDefinitions;
use App\Domains\Tasks\Observers\TaskCategoryObserver;
use App\Domains\Tasks\Permissions\TaskCategoryPermissions;
use App\Domains\Tasks\Permissions\TaskPermissions;
use App\Domains\Tasks\Permissions\TaskTemplatePermissions;
use App\Domains\Tasks\Policies\TaskCategoryPolicy;
use App\Domains\Tasks\Policies\TaskPolicy;
use App\Domains\Tasks\Policies\TaskTemplatePolicy;
use App\Domains\Tasks\Support\TasksProjectTab;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, SettingsRegistryContract $settingsRegistry, ReportRegistry $reportRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerReports($reportRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskCategory::class, TaskCategoryPolicy::class);
        Gate::policy(TaskTemplate::class, TaskTemplatePolicy::class);
        TaskCategory::observe(TaskCategoryObserver::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'tasks');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('tasks', classNamespace: 'App\Domains\Tasks\Livewire');
        Livewire::component('tasks.admin.projects.task-hierarchy-metrics', TaskHierarchyMetrics::class);
        Livewire::component('tasks.admin.projects.task-hierarchy-templates', TaskHierarchyTemplates::class);
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
        $definitions = [
            ...TaskPermissions::all(),
            ...TaskCategoryPermissions::all(),
            ...TaskTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions($definitions);
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(TaskNotificationDefinitions::definitions());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'operational.tasks',
                'section' => 'operational',
                'title' => 'Task Operations',
                'description' => 'Review open work, due tasks, and execution progress.',
                'route' => 'tasks.index',
                'badge_label' => 'Operational',
                'badge_color' => 'sky',
                'sort' => 40,
            ],
        ]);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('tasks', __DIR__.'/../config/settings.php');
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            TasksProjectTab::class,
        ]);
    }
}
