<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Livewire\Admin\Projects\Show as ProjectShow;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectUserAccess;
use Livewire\Livewire;

it('shows access tab on admin project page when user can view project access', function (): void {
    $user = userWithProjectAccessUiPermissions([
        'projects.view',
        'project-access.view',
    ]);

    $project = Project::factory()->create([
        'name' => 'Access Tab Visibility Project',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Access');
});

it('grants and revokes project access from the admin project show component', function (): void {
    $manager = userWithProjectAccessUiPermissions([
        'projects.view',
        'project-access.view',
        'project-access.grant',
        'project-access.revoke',
    ]);

    $targetUser = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    $this->actingAs($manager);

    $component = Livewire::test(ProjectShow::class, ['project' => $project])
        ->set('activeTab', 'access')
        ->set('selectedAccessUserId', (string) $targetUser->id)
        ->call('grantProjectAccess')
        ->assertHasNoErrors();

    expect(ProjectUserAccess::query()
        ->where('project_id', $project->id)
        ->where('user_id', $targetUser->id)
        ->exists())->toBeTrue();

    $component
        ->call('revokeProjectAccess', (string) $targetUser->id)
        ->assertHasNoErrors();

    expect(ProjectUserAccess::query()
        ->where('project_id', $project->id)
        ->where('user_id', $targetUser->id)
        ->exists())->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithProjectAccessUiPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Project Access UI Role '.str()->uuid(),
        'description' => 'Role for project access admin UI tests',
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
