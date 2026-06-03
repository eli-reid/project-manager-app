<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Projects\Services\ProjectTabRegistry;

it('defines project tab mode query params for routed create modes', function (): void {
    $registry = app(ProjectTabRegistry::class);

    expect($registry->modeQueryParam('overview'))->toBeNull()
        ->and($registry->modeQueryParam('submittals'))->toBe('submittalMode')
        ->and($registry->modeQueryParam('change-orders'))->toBe('changeOrderMode')
        ->and($registry->modeQueryParam('invoices'))->toBe('invoiceMode')
        ->and($registry->modeQueryParam('rfis'))->toBe('rfiMode');
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
