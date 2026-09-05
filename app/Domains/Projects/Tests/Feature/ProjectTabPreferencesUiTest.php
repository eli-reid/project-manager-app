<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Livewire\Admin\Projects\Show as ProjectShow;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Projects\Services\ProjectTabRegistry;
use Livewire\Livewire;

it('persists project tab drag and drop ordering from the project page', function (): void {
    $user = userWithProjectTabUiPermissions([
        'projects.view',
        'tasks.view',
        'invoices.view',
        'timecards.view',
    ]);

    $project = Project::factory()->create();

    $this->actingAs($user);

    Livewire::test(ProjectShow::class, ['project' => $project])
        ->call('sortProjectTab', 'time', 1)
        ->assertHasNoErrors();

    $preferences = ProjectTabUserPreference::query()
        ->where('user_id', $user->id)
        ->pluck('sort_order', 'tab_key')
        ->all();

    expect($preferences['overview'] ?? null)->toBe(1)
        ->and($preferences['time'] ?? null)->toBe(2)
        ->and($preferences['tasks'] ?? null)->toBe(3)
        ->and($preferences['invoices'] ?? null)->toBe(4)
        ->and(app(ProjectTabRegistry::class)->visibleTabs($project, $user->fresh()))->toBe(['overview', 'time', 'tasks', 'invoices']);
});

it('hides and restores project tabs from the project page', function (): void {
    $user = userWithProjectTabUiPermissions([
        'projects.view',
        'tasks.view',
        'invoices.view',
    ]);

    $project = Project::factory()->create();

    $this->actingAs($user);

    Livewire::test(ProjectShow::class, ['project' => $project])
        ->set('activeTab', 'invoices')
        ->call('hideTab', 'invoices')
        ->assertSet('activeTab', 'overview')
        ->call('showTab', 'invoices')
        ->assertHasNoErrors();

    $invoicePreference = ProjectTabUserPreference::query()
        ->where('user_id', $user->id)
        ->where('tab_key', 'invoices')
        ->first();

    expect($invoicePreference)->not->toBeNull()
        ->and($invoicePreference?->is_hidden)->toBeFalse()
        ->and(app(ProjectTabRegistry::class)->visibleTabs($project, $user->fresh()))->toContain('invoices');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithProjectTabUiPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Project Tab UI Role '.str()->uuid(),
        'description' => 'Role for project tab UI tests',
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
