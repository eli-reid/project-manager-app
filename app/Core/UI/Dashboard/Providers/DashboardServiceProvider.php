<?php

namespace App\Core\UI\Dashboard\Providers;

use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\DTO\NavSectionEnum;
use App\Core\UI\Navigation\Services\NavigationManager;
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

    public function boot(DashboardPanelRegistry $panelRegistry, NavigationManager $navigationManager): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'dashboard');
        $this->registerPanels($panelRegistry);
        $this->app->booted(fn (): bool => $this->syncPanelNavigation($panelRegistry, $navigationManager));
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
                navigationSectionKey: 'workspace',
                navigationSectionLabel: 'Workspace',
                navigationSectionOrder: 10,
            ),
        ]);
    }

    private function syncPanelNavigation(DashboardPanelRegistry $panelRegistry, NavigationManager $navigationManager): bool
    {
        foreach ($panelRegistry->all() as $panel) {
            $sectionKey = (string) ($panel['navigation_section_key'] ?? 'workspace');

            $navigationManager->registerSection(
                $sectionKey,
                (string) ($panel['navigation_section_label'] ?? 'Workspace'),
                (int) ($panel['navigation_section_order'] ?? 10),
            );

            $panelKey = (string) $panel['key'];

            $navigationManager->registerItem($sectionKey, $panel['navigation_group'] ?? null, new NavItem(
                id: 'dashboard-panel-'.$panelKey,
                label: (string) $panel['label'],
                icon: (string) ($panel['icon'] ?? ''),
                url: url('/dashboard?panel='.urlencode($panelKey)),
                route: null,
                group: $panel['navigation_group'] ?? null,
                order: (int) ($panel['sort'] ?? 100),
                active: false,
                visible: true,
                permissions: [],
                section: NavSectionEnum::DASHBOARD,
                meta: [
                    'description' => (string) ($panel['description'] ?? ''),
                    'badge' => (string) ($panel['badge'] ?? ''),
                    'active_query' => ['panel' => $panelKey],
                    'default_query' => $panelKey === 'overview' ? ['panel' => 'overview'] : [],
                ],
            ));
        }

        return true;
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
