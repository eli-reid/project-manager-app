<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;

it('registers user tasks route', function (): void {
    expect(route('tasks.index', absolute: false))->toBe('/tasks');
});

it('allows users with tasks view permission to access task list', function (): void {
    $user = taskUserWithPermissions(['tasks.view']);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertSuccessful()
        ->assertSee('Tasks');
});

it('forbids users without tasks view permission from task list', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertForbidden();
});

it('shows assigned tasks for read-only users', function (): void {
    $user = taskUserWithPermissions(['tasks.view']);
    $project = Project::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Assigned Task',
        'assigned_to' => $user->id,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Other User Task',
        'assigned_to' => User::factory()->create()->id,
    ]);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertSuccessful()
        ->assertSee('Assigned Task')
        ->assertDontSee('Other User Task');
});

/**
 * @param  array<int, string>  $permissions
 */
function taskUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Task Test Role '.str()->uuid(),
        'description' => 'Role created by task user tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            $permissionId = Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');

            return is_string($permissionId) ? $permissionId : null;
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
