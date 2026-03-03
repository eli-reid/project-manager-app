<?php

namespace App\Core\Scheduler\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Core\Scheduler\Services\TaskTypeRegistry;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaskTypeRegistry::class);
    }

    public function boot(): void
    {
        $this->configureRoutes();
        $this->configureViews();
        $this->configureComponents();
    }

    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'scheduler');
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
            ->middleware(['web', 'auth', 'can:access-admin'])
            ->group(__DIR__ . '/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__ . '/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__ . '/../Routes/api.php');
    }
}

