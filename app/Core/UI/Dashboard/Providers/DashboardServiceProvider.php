<?php

namespace App\Core\UI\Dashboard\Providers;

use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardWidgetRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'dashboard');
        $this->registerRoutes();
        $this->registerUIComponents();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/mobile.php');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('dashboard', classNamespace: 'App\Core\UI\Dashboard\Livewire');
    }
}
