<?php

namespace App\Core\Settings\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Core\Scheduler\Services\TaskTypeRegistry;

class SettingsServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'settings');
    }

    private function configureComponents(): void
    {
        Blade::componentNamespace(
            '\\App\\Core\\Settings\\View\\Components',
            'settings'
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

