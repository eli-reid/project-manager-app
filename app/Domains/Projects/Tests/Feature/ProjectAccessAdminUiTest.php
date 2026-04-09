<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Livewire\Admin\Projects\Show as ProjectShow;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
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

it('stores selected project action permissions when granting access from admin ui', function (): void {
    $manager = userWithProjectAccessUiPermissions([
        'projects.view',
        'project-access.view',
        'project-access.grant',
    ]);

    $targetUser = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    $this->actingAs($manager);

    Livewire::test(ProjectShow::class, ['project' => $project])
        ->set('activeTab', 'access')
        ->set('selectedAccessUserId', (string) $targetUser->id)
        ->set('selectedAccessPermissionKeys', ['projects.view', 'projects.edit'])
        ->call('grantProjectAccess')
        ->assertHasNoErrors();

    $assignment = ProjectUserAccess::query()
        ->where('project_id', $project->id)
        ->where('user_id', $targetUser->id)
        ->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment?->permission_keys)->toContain('projects.view')
        ->and($assignment?->permission_keys)->toContain('projects.edit');
});

it('grants and revokes role-based project access from the admin project show component', function (): void {
    $manager = userWithProjectAccessUiPermissions([
        'projects.view',
        'project-access.view',
        'project-access.grant',
        'project-access.revoke',
    ]);

    $project = Project::factory()->create();

    $role = Role::query()->create([
        'name' => 'Project Group '.str()->uuid(),
        'description' => 'Role-based project access group',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 15,
    ]);

    $this->actingAs($manager);

    $component = Livewire::test(ProjectShow::class, ['project' => $project])
        ->set('activeTab', 'access')
        ->set('selectedAccessRoleId', (string) $role->id)
        ->set('selectedAccessPermissionKeys', ['projects.view', 'projects.edit'])
        ->call('grantProjectRoleAccess')
        ->assertHasNoErrors();

    expect(ProjectRoleAccess::query()
        ->where('project_id', $project->id)
        ->where('role_id', $role->id)
        ->exists())->toBeTrue();

    $component
        ->call('revokeProjectRoleAccess', (string) $role->id)
        ->assertHasNoErrors();

    expect(ProjectRoleAccess::query()
        ->where('project_id', $project->id)
        ->where('role_id', $role->id)
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
