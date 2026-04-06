<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Services\ProjectAccessService;

it('grants project access and records an audit log', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $assignee = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    $access = app(ProjectAccessService::class)->grant(
        $project,
        $assignee,
        $actor,
        ['projects.view', 'projects.edit']
    );

    expect($access->project_id)->toBe((string) $project->id)
        ->and($access->user_id)->toBe((string) $assignee->id)
        ->and($access->granted_by)->toBe((string) $actor->id)
        ->and($access->permission_keys)->toBe(['projects.view', 'projects.edit']);

    $audit = AuditLog::query()->where('action', 'project-access.grant')->first();

    expect($audit)->not->toBeNull()
        ->and($audit?->target_type)->toBe($project->getMorphClass())
        ->and($audit?->target_id)->toBe((string) $project->id)
        ->and($audit?->metadata['assignee_user_id'] ?? null)->toBe((string) $assignee->id);
});

it('revokes project access and records an audit log', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant', 'project-access.revoke']);
    $assignee = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    app(ProjectAccessService::class)->grant($project, $assignee, $actor, ['projects.view']);

    app(ProjectAccessService::class)->revoke($project, $assignee, $actor);

    expect(ProjectUserAccess::query()->where('project_id', $project->id)->where('user_id', $assignee->id)->exists())
        ->toBeFalse();

    $audit = AuditLog::query()->where('action', 'project-access.revoke')->first();

    expect($audit)->not->toBeNull()
        ->and($audit?->metadata['assignee_user_id'] ?? null)->toBe((string) $assignee->id);
});

it('keeps broad project visibility when scoped access has not been configured', function (): void {
    $user = userWithAccessPermissions(['projects.view']);
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertSuccessful();
});

it('enforces scoped access entries on project view route when configured', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $allowedUser = userWithAccessPermissions(['projects.view']);
    $blockedUser = userWithAccessPermissions(['projects.view']);
    $project = Project::factory()->create();

    app(ProjectAccessService::class)->grant($project, $allowedUser, $actor, ['projects.view']);

    $this->actingAs($allowedUser)
        ->get(route('projects.show', $project))
        ->assertSuccessful();

    $this->actingAs($blockedUser)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithAccessPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Project Access Role '.str()->uuid(),
        'description' => 'Role for project access feature tests',
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
