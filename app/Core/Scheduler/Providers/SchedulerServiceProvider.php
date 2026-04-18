<?php

namespace App\Core\Scheduler\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Scheduler\Commands\DeployUpgradeCommand;
use App\Core\Scheduler\Commands\SyncSchedulerTasksCommand;
use App\Core\Scheduler\Livewire\Admin\Tasks\Form;
use App\Core\Scheduler\Livewire\Admin\Tasks\Index;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Permissions\SchedulerPermissions;
use App\Core\Scheduler\Policies\ScheduledTaskPolicy;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\SchedulerService;
use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
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

    public function boot(): void
    {
        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerSettings();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerCommands();

        $this->app->booted(function (): void {
            $this->app->make(TaskDefinitionSyncService::class)->syncSafely();
        });
    }

    private function registerAuthorization(): void
    {
        Gate::policy(ScheduledTask::class, ScheduledTaskPolicy::class);
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);

        $registry->registerPermissions(SchedulerPermissions::all());
    }

    private function registerSettings(): void
    {
        if (! $this->app->bound(SettingsRegistryContract::class)) {
            return;
        }

        /** @var SettingsRegistryContract $registry */
        $registry = $this->app->make(SettingsRegistryContract::class);
        $registry->registerConfigFile('scheduler', __DIR__.'/../config/settings.php');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.core.scheduler.livewire.admin.tasks', Index::class);
        Livewire::component('app.core.scheduler.livewire.admin.tasks.form', Form::class);
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
            ->middleware(['api', 'auth:sanctum'])
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
