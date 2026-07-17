<?php

namespace App\Core\Scheduler\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\UI\Dashboard\Data\WidgetDefinition;
use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\Scheduler\Commands\DeployUpgradeCommand;
use App\Core\Scheduler\Commands\SyncSchedulerTasksCommand;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Permissions\SchedulerPermissions;
use App\Core\Scheduler\Policies\ScheduledTaskPolicy;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\SchedulerService;
use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaskTypeRegistry::class, function (): TaskTypeRegistry {
            return new TaskTypeRegistry;
        });

        $this->app->singleton(ScheduledTaskService::class, function (): ScheduledTaskService {
            return new ScheduledTaskService;
        });

        $this->app->singleton(ScheduledTaskFactory::class, function ($app): ScheduledTaskFactory {
            return new ScheduledTaskFactory($app->make(TaskTypeRegistry::class));
        });

        $this->app->singleton(TaskDefinitionSyncService::class, function ($app): TaskDefinitionSyncService {
            return new TaskDefinitionSyncService(
                $app->make(TaskTypeRegistry::class),
            );
        });

        $this->app->singleton(SchedulerService::class, function (): SchedulerService {
            return new SchedulerService;
        });
    }

    public function boot(PermissionRegistryContract $permissionRegistry, DashboardWidgetRegistry $widgetRegistry, TaskDefinitionSyncService $taskDefinitionSyncService): void
    {
        $this->registerAuthorization();
        $this->registerPermissions($permissionRegistry);
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerDashboardWidgets($widgetRegistry);
        $this->registerRoutes();
        $this->registerCommands();

        $this->app->booted(function () use ($taskDefinitionSyncService): void {
            $taskDefinitionSyncService->syncSafely();
        });
    }

    private function registerAuthorization(): void
    {
        Gate::policy(ScheduledTask::class, ScheduledTaskPolicy::class);
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(SchedulerPermissions::all());
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('scheduler', classNamespace: 'App\Core\Scheduler\Livewire');
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        $widgetRegistry->registerDefinitions([
            new WidgetDefinition(
                key: 'scheduler.task-health',
                component: 'scheduler::dashboard.widget',
                section: 'admin',
                sort: 10,
                span: 'full',
                ability: 'viewAny',
                abilityModel: ScheduledTask::class,
                title: 'Scheduler Health',
                description: 'Scheduled task status overview.',
            ),
        ]);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'scheduler');

        Blade::componentNamespace(
            'App\\Core\\Scheduler\\View\\Components',
            'scheduler'
        );
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth'])
            ->group(__DIR__.'/../Routes/api.php');
    }

    private function registerCommands(): void
    {
        $this->commands([
            SyncSchedulerTasksCommand::class,
            DeployUpgradeCommand::class,
        ]);
    }
}
