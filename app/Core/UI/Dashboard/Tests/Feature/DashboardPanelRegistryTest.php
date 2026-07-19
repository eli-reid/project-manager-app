<?php

use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use App\Core\UI\Navigation\Services\NavigationManager;
use Illuminate\Support\Facades\Log;

it('registers dashboard panels in sort order', function (): void {
    $registry = new DashboardPanelRegistry;

    $registry->registerDefinitions([
        new PanelDefinition(key: 'reports', component: 'dashboard::panels.reports', sort: 20, label: 'Reports'),
        new PanelDefinition(key: 'overview', component: 'dashboard::panels.overview', sort: 10, label: 'Overview'),
    ]);

    $panels = $registry->all();

    expect($panels)->toHaveCount(2)
        ->and($panels[0]['key'])->toBe('overview')
        ->and($panels[1]['key'])->toBe('reports');
});

it('ignores duplicate dashboard panel keys and logs a warning', function (): void {
    $registry = new DashboardPanelRegistry;

    Log::shouldReceive('warning')
        ->once()
        ->with('DashboardPanelRegistry: duplicate key ignored during registerDefinitions.', Mockery::on(
            fn (array $context): bool => $context['key'] === 'overview'
        ));

    $registry->registerDefinitions([
        new PanelDefinition(key: 'overview', component: 'dashboard::panels.overview', label: 'Overview'),
        new PanelDefinition(key: 'overview', component: 'dashboard::panels.duplicate', label: 'Duplicate'),
    ]);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['component'])->toBe('dashboard::panels.overview');
});

it('registers the built-in overview panel in the container registry', function (): void {
    $registry = app(DashboardPanelRegistry::class);

    $panel = collect($registry->all())->firstWhere('key', 'overview');

    expect($panel)->not->toBeNull()
        ->and($panel['component'])->toBe('dashboard::panels.overview')
        ->and($panel['label'])->toBe('Overview');
});

it('projects dashboard panels into the navigation domain after boot', function (): void {
    $navigationManager = app(NavigationManager::class);

    $workspaceSection = collect($navigationManager->resolve())
        ->firstWhere('key', 'workspace');

    expect($workspaceSection)->not->toBeNull()
        ->and(collect($workspaceSection['items'])->pluck('id')->all())->toContain('dashboard-panel-overview');
});
