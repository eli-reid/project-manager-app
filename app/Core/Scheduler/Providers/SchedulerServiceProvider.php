<?php

namespace App\Core\Scheduler\Providers;

use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\SchedulerService;
use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaskTypeRegistry::class, function (): TaskTypeRegistry {
            $registry = new TaskTypeRegistry;

            $registry->register('timecard_reminders', NoOpTask::class, [
                'name' => 'Timecard Reminders',
                'description' => 'Send periodic timecard reminders.',
            ]);
            $registry->register('automated_reports', NoOpTask::class, [
                'name' => 'Automated Reports',
                'description' => 'Generate and distribute scheduled reports.',
            ]);
            $registry->register('database_backup', NoOpTask::class, [
                'name' => 'Database Backup',
                'description' => 'Run scheduled database backup tasks.',
            ]);
            $registry->register('system_cleanup', NoOpTask::class, [
                'name' => 'System Cleanup',
                'description' => 'Run periodic cleanup routines.',
            ]);

            return $registry;
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
                $app->make(ScheduledTaskService::class),
            );
        });

        $this->app->singleton(SchedulerService::class, function (): SchedulerService {
            return new SchedulerService;
        });
    }

    public function boot(): void
    {
        $this->configureRoutes();
        $this->configureViews();
        $this->configureComponents();
        $this->registerLivewireComponents();

        $this->app->booted(function (): void {
            app(TaskDefinitionSyncService::class)->syncSafely();
        });
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('app.core.scheduler.livewire.admin.tasks', \App\Core\Scheduler\Livewire\Admin\Tasks\Index::class);
        Livewire::component('app.core.scheduler.livewire.admin.tasks.form', \App\Core\Scheduler\Livewire\Admin\Tasks\Form::class);
    }

    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'scheduler');
    }

    private function configureComponents(): void
    {
        Blade::componentNamespace(
            'App\\Core\\Scheduler\\View\\Components',
            'scheduler'
        );
    }

    private function configureRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:admin'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
