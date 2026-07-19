<?php

use App\Core\Identity\Models\User;
use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\DTO\NavSectionEnum;
use App\Core\UI\Navigation\Services\NavigationManager;
use App\PlugIns\Contracts\PluginContextFactory;
use App\PlugIns\Contracts\PluginHost;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

it('lets the host register plugin panels and navigation items through a narrow bridge', function (): void {
    $host = app(PluginHost::class);

    $host->registerPanel(new PanelDefinition(
        key: 'plugin.tasks-board',
        component: 'plugin::panels.tasks-board',
        icon: 'clipboard-document-list',
        sort: 60,
        label: 'Tasks Board',
        description: 'Plugin-powered task board panel.',
        navigationSectionKey: 'workspace',
        navigationSectionLabel: 'Workspace',
        navigationSectionOrder: 10,
    ));

    $host->registerNavigationItem('workspace', null, new NavItem(
        id: 'plugin-menu-item',
        label: 'Plugin Menu Item',
        icon: 'puzzle-piece',
        url: '/plugin/menu-item',
        route: null,
        group: null,
        order: 90,
        active: false,
        visible: true,
        permissions: [],
        section: NavSectionEnum::DASHBOARD,
        meta: ['plugin' => 'example-plugin'],
    ));

    $registeredPanel = collect(app(DashboardPanelRegistry::class)->all())
        ->firstWhere('key', 'plugin.tasks-board');

    expect($registeredPanel)->not->toBeNull()
        ->and($registeredPanel['component'])->toBe('plugin::panels.tasks-board');

    $resolvedNavigation = collect(app(NavigationManager::class)->resolve())
        ->firstWhere('key', 'workspace');

    expect(collect($resolvedNavigation['items'] ?? [])->pluck('id')->all())
        ->toContain('plugin-menu-item');
});

it('lets plugins request approved data through the runtime context', function (): void {
    $user = User::factory()->create(['first_name' => 'Plugin', 'last_name' => 'Operator']);
    $this->actingAs($user);

    Gate::define('projects.view-summary', fn (User $user): bool => $user->is_active);

    app(PluginHost::class)->registerDataProvider(
        key: 'projects.summary',
        resolver: fn (array $parameters, $currentUser, string $pluginId): array => [
            'plugin' => $pluginId,
            'project_id' => $parameters['project_id'] ?? null,
            'requested_by' => $currentUser?->id,
        ],
        allowedCallers: ['field-ops-plugin'],
        requiredAbility: 'projects.view-summary',
    );

    $context = app(PluginContextFactory::class)->make('field-ops-plugin');
    $payload = $context->requestData('projects.summary', ['project_id' => 'proj-123']);

    expect($payload)->toBe([
        'plugin' => 'field-ops-plugin',
        'project_id' => 'proj-123',
        'requested_by' => $user->id,
    ]);
});

it('blocks plugins from requesting data outside their caller allowlist', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(PluginHost::class)->registerDataProvider(
        key: 'projects.private-feed',
        resolver: fn (): array => ['ok' => true],
        allowedCallers: ['approved-plugin'],
    );

    $context = app(PluginContextFactory::class)->make('blocked-plugin');

    expect(fn () => $context->requestData('projects.private-feed'))
        ->toThrow(AuthorizationException::class);
});

it('blocks plugins when the current user lacks the required ability', function (): void {
    $user = User::factory()->create(['is_active' => false]);
    $this->actingAs($user);

    Gate::define('projects.manage-sensitive', fn (): bool => false);

    app(PluginHost::class)->registerDataProvider(
        key: 'projects.sensitive-metrics',
        resolver: fn (): array => ['ok' => true],
        allowedCallers: ['field-ops-plugin'],
        requiredAbility: 'projects.manage-sensitive',
    );

    $context = app(PluginContextFactory::class)->make('field-ops-plugin');

    expect(fn () => $context->requestData('projects.sensitive-metrics'))
        ->toThrow(AuthorizationException::class);
});
