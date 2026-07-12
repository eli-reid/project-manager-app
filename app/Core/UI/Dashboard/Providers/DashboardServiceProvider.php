<?php

namespace App\Core\UI\Dashboard\Providers;

use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardPanelRegistry::class);
        $this->app->singleton(DashboardWidgetRegistry::class);
    }

    public function boot(DashboardPanelRegistry $panelRegistry): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'dashboard');
        $this->registerPanels($panelRegistry);
        $this->registerRoutes();
        $this->registerUIComponents();
    }

    private function registerPanels(DashboardPanelRegistry $panelRegistry): void
    {
        $panelRegistry->registerDefinitions([
            new PanelDefinition(
                key: 'overview',
                component: 'dashboard::panels.overview',
                icon: 'squares-2x2',
                sort: 10,
                label: 'Overview',
                description: 'Operational snapshot across all registered dashboard widgets.',
            ),
        ]);
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
