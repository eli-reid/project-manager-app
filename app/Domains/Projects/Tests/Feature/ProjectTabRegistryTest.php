<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Support\ChangeOrderTab;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show;
use App\Domains\Dailies\Support\DailiesProjectTab;
use App\Domains\Invoices\Support\InvoicesProjectTab;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Projects\Services\ProjectTabLinkBuilder;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Projects\Support\AccessProjectTab;
use App\Domains\RFIs\Support\RFIsProjectTab;
use App\Domains\Stock\Support\StockProjectTab;
use App\Domains\Submittals\Support\SubmittalsProjectTab;
use App\Domains\Tasks\Support\TasksProjectTab;
use App\Domains\Timecards\Support\TimecardsProjectTab;
use Illuminate\Support\Facades\DB;

it('defines project tab mode query params for routed create modes', function (): void {
    $registry = app(ProjectTabRegistry::class);

    expect($registry->modeQueryParam('overview'))->toBeNull()
        ->and($registry->modeQueryParam('submittals'))->toBe('submittalMode')
        ->and($registry->modeQueryParam('change-orders'))->toBe('changeOrderMode')
        ->and($registry->modeQueryParam('invoices'))->toBe('invoiceMode')
        ->and($registry->modeQueryParam('rfis'))->toBe('rfiMode');
});

it('includes the expected provider-registered project view tabs', function (): void {
    $registry = app(ProjectTabRegistry::class);

    expect(array_keys($registry->tabs()))->toEqual([
        'overview',
        'dailies',
        'tasks',
        'invoices',
        'stock',
        'submittals',
        'change-orders',
        'rfis',
        'documents',
        'access',
        'time',
        'financials',
    ]);
});

it('returns visible tabs for the current project and user permissions', function (): void {
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    $limitedUser = userWithProjectTabPermissions([
        'projects.view',
    ]);

    expect($registry->visibleTabs($project, $limitedUser))->toBe(['overview']);

    $broadUser = userWithProjectTabPermissions([
        'projects.view',
        'dailies.view-all',
        'tasks.view',
        'invoices.view',
        'submittals.view-any',
        'change-orders.view',
        'rfis.view-any',
        'timecards.view',
        'project-access.view',
        'projects.view-financials',
    ]);

    $visibleTabs = $registry->visibleTabs($project, $broadUser);

    expect($visibleTabs)->toContain('overview')
        ->toContain('dailies')
        ->toContain('tasks')
        ->toContain('invoices')
        ->toContain('submittals')
        ->toContain('change-orders')
        ->toContain('rfis')
        ->toContain('access')
        ->toContain('time')
        ->toContain('financials')
        ->not->toContain('documents')
        ->and($registry->isCreateMode('submittals', request()->duplicate(query: ['submittalMode' => 'create'])))->toBeTrue()
        ->and($registry->isCreateMode('submittals', request()->duplicate(query: ['submittalMode' => 'review'])))->toBeFalse();
});

it('applies project tab table overrides to provider-registered tab definitions', function (): void {
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    ProjectTabDefinition::query()->updateOrCreate(
        ['key' => 'rfis'],
        [
            'label' => 'RFIs',
            'mode_query_param' => 'rfiMode',
            'sort_order' => 80,
            'is_active' => false,
        ]
    );

    ProjectTabDefinition::query()->updateOrCreate(
        ['key' => 'submittals'],
        [
            'label' => 'Submittals',
            'mode_query_param' => 'submittalAction',
            'sort_order' => 60,
            'is_active' => true,
        ]
    );

    $user = userWithProjectTabPermissions([
        'projects.view',
        'submittals.view-any',
        'rfis.view-any',
    ]);

    $visibleTabs = $registry->visibleTabs($project, $user);

    expect($visibleTabs)->toContain('submittals')
        ->not->toContain('rfis')
        ->and($registry->modeQueryParam('submittals'))->toBe('submittalAction')
        ->and($registry->isCreateMode('submittals', request()->duplicate(query: ['submittalAction' => 'create'])))->toBeTrue();
});

it('applies user tab ordering and hidden state on top of registered tabs', function (): void {
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    $user = userWithProjectTabPermissions([
        'projects.view',
        'tasks.view',
        'invoices.view',
        'timecards.view',
    ]);

    ProjectTabUserPreference::query()->create([
        'user_id' => $user->id,
        'tab_key' => 'overview',
        'sort_order' => 1,
        'is_hidden' => false,
    ]);

    ProjectTabUserPreference::query()->create([
        'user_id' => $user->id,
        'tab_key' => 'time',
        'sort_order' => 2,
        'is_hidden' => false,
    ]);

    ProjectTabUserPreference::query()->create([
        'user_id' => $user->id,
        'tab_key' => 'tasks',
        'sort_order' => 3,
        'is_hidden' => false,
    ]);

    ProjectTabUserPreference::query()->create([
        'user_id' => $user->id,
        'tab_key' => 'invoices',
        'sort_order' => 4,
        'is_hidden' => true,
    ]);

    expect($registry->visibleTabs($project, $user))->toBe(['overview', 'time', 'tasks'])
        ->and(collect($registry->hiddenTabItems($project, $user))->pluck('key')->all())->toBe(['invoices']);
});

it('builds project tab urls from registered metadata', function (): void {
    $project = Project::factory()->create();
    $linkBuilder = app(ProjectTabLinkBuilder::class);
    $submittalsBaseUrl = $linkBuilder->to($project, 'submittals', absolute: false);

    expect($linkBuilder->to($project, 'submittals', mode: 'review', detailId: 'sub-123', absolute: false))
        ->toContain($submittalsBaseUrl)
        ->toContain('submittalMode=review')
        ->toContain('submittalId=sub-123');

    ProjectTabDefinition::query()->updateOrCreate(
        ['key' => 'submittals'],
        [
            'label' => 'Submittals',
            'mode_query_param' => 'submittalAction',
            'sort_order' => 60,
            'is_active' => true,
        ]
    );

    app()->forgetInstance(ProjectTabRegistry::class);
    app()->forgetInstance(ProjectTabLinkBuilder::class);

    $linkBuilder = app(ProjectTabLinkBuilder::class);
    $submittalsBaseUrl = $linkBuilder->to($project, 'submittals', absolute: false);

    expect($linkBuilder->to($project, 'submittals', mode: 'create', detailId: 'sub-456', absolute: false))
        ->toContain($submittalsBaseUrl)
        ->toContain('submittalAction=create')
        ->toContain('submittalId=sub-456');
});

it('passes return urls to dailies detail panels through view state', function (): void {
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    $user = userWithProjectTabPermissions([
        'projects.view',
        'dailies.view-all',
    ]);

    $tabPanels = $registry->tabPanels(
        $project,
        $user,
        [
            'dailies' => [
                'modeParam' => 'dailyMode',
                'mode' => '',
                'detailParam' => 'dailyId',
                'detailId' => 'daily-123',
                'isCreateMode' => false,
            ],
        ],
        [
            'returnTo' => [
                'dailies' => '/admin/projects/test?tab=dailies',
            ],
        ],
    );

    $dailiesPanel = collect($tabPanels)->firstWhere('tab', 'dailies');

    expect($dailiesPanel)->not->toBeNull()
        ->and($dailiesPanel['component'] ?? null)->toBe(Show::class)
        ->and($dailiesPanel['props']['returnTo'] ?? null)->toBe('/admin/projects/test?tab=dailies');
});

it('preloads badge count attributes for visible project tabs', function (): void {
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    $user = userWithProjectTabPermissions([
        'projects.view',
        'dailies.view-all',
        'tasks.view',
        'invoices.view',
        'submittals.view-any',
        'change-orders.view',
        'rfis.view-any',
        'timecards.view',
        'project-access.view',
    ]);

    $registry->loadBadgeCounts($project, $user);

    expect($project->getAttribute('daily_reports_count'))->not->toBeNull()
        ->and($project->getAttribute('tasks_count'))->not->toBeNull()
        ->and($project->getAttribute('invoices_count'))->not->toBeNull()
        ->and($project->getAttribute('stock_orders_count'))->toBeNull()
        ->and($project->getAttribute('submittals_count'))->not->toBeNull()
        ->and($project->getAttribute('change_orders_count'))->not->toBeNull()
        ->and($project->getAttribute('rfis_count'))->not->toBeNull()
        ->and($project->getAttribute('user_accesses_count'))->not->toBeNull()
        ->and($project->getAttribute('role_accesses_count'))->not->toBeNull()
        ->and($project->getAttribute('timecard_entries_count'))->not->toBeNull();
});

it('uses preloaded badge count attributes for count-based tabs', function (): void {
    $project = Project::factory()->make(['id' => (string) str()->ulid()]);
    $user = userWithProjectTabPermissions([
        'projects.view',
        'submittals.view-any',
    ])->load('roles.permissions');

    $project->forceFill([
        'daily_reports_count' => 2,
        'tasks_count' => 3,
        'invoices_count' => 4,
        'stock_orders_count' => 5,
        'submittals_count' => 6,
        'change_orders_count' => 7,
        'rfis_count' => 8,
        'user_accesses_count' => 9,
        'role_accesses_count' => 10,
        'timecard_entries_count' => 11,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect(app(DailiesProjectTab::class)->badgeCount($user, $project))->toBe(2)
        ->and(app(TasksProjectTab::class)->badgeCount($user, $project))->toBe(3)
        ->and(app(InvoicesProjectTab::class)->badgeCount($user, $project))->toBe(4)
        ->and(app(StockProjectTab::class)->badgeCount($user, $project))->toBe(5)
        ->and(app(SubmittalsProjectTab::class)->badgeCount($user, $project))->toBe(6)
        ->and(app(ChangeOrderTab::class)->badgeCount($user, $project))->toBe(7)
        ->and(app(RFIsProjectTab::class)->badgeCount($user, $project))->toBe(8)
        ->and(app(AccessProjectTab::class)->badgeCount($user, $project))->toBe(19)
        ->and(app(TimecardsProjectTab::class)->badgeCount($user, $project))->toBe(11)
        ->and(collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains(strtolower($query), 'count('))->all())->toBe([]);
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithProjectTabPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Project Tab Registry Role '.str()->uuid(),
        'description' => 'Role for project tab registry tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
