<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;

it('allows legacy tasks edit permission to update all task edit fields', function (): void {
    $user = userWithTaskPermissions(['tasks.edit']);
    $task = Task::factory()->create();

    expect($user->can('update', $task))->toBeTrue();
    expect($user->can('updateStatus', $task))->toBeTrue();
    expect($user->can('updatePriority', $task))->toBeTrue();
    expect($user->can('updateAssignee', $task))->toBeTrue();
    expect($user->can('updateProgress', $task))->toBeTrue();
    expect($user->can('updateNotes', $task))->toBeTrue();
});

it('maps explicit status edit permission to updateStatus policy action', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-status']);
    $task = Task::factory()->create();

    expect($user->can('updateStatus', $task))->toBeTrue();
    expect($user->can('updatePriority', $task))->toBeFalse();
});

it('maps explicit priority edit permission to updatePriority policy action', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-priority']);
    $task = Task::factory()->create();

    expect($user->can('updatePriority', $task))->toBeTrue();
    expect($user->can('updateStatus', $task))->toBeFalse();
});

it('maps explicit assignee edit permission to updateAssignee policy action', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-assignee']);
    $task = Task::factory()->create();

    expect($user->can('updateAssignee', $task))->toBeTrue();
    expect($user->can('updateNotes', $task))->toBeFalse();
});

it('maps explicit progress edit permission to updateProgress policy action', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-progress']);
    $task = Task::factory()->create();

    expect($user->can('updateProgress', $task))->toBeTrue();
    expect($user->can('updateStatus', $task))->toBeFalse();
});

it('maps explicit notes edit permission to updateNotes policy action', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-notes']);
    $task = Task::factory()->create();

    expect($user->can('updateNotes', $task))->toBeTrue();
    expect($user->can('updatePriority', $task))->toBeFalse();
});

it('does not grant update when only granular permission exists', function (): void {
    $user = userWithTaskPermissions(['tasks.edit-status']);
    $task = Task::factory()->create();

    expect($user->can('update', $task))->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithTaskPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);
    Project::factory()->create();

    $role = Role::query()->create([
        'name' => 'Task Policy Role '.str()->uuid(),
        'description' => 'Role created by task policy tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 30,
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
