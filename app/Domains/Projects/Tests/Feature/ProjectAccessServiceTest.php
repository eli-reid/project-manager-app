<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Notifications\ProjectAccessGrantedNotification;
use App\Domains\Projects\Notifications\ProjectAccessRevokedNotification;
use App\Domains\Projects\Services\ProjectAccessService;
use Illuminate\Support\Facades\Notification;

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

it('enforces scoped access entries through role-based project access', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $allowedUser = userWithAccessPermissions(['projects.view']);
    $blockedUser = userWithAccessPermissions(['projects.view']);
    $project = Project::factory()->create();

    $role = Role::query()->create([
        'name' => 'Scoped Access Role '.str()->uuid(),
        'description' => 'Role for scoped project access visibility',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    $allowedUser->roles()->syncWithoutDetaching([$role->id]);

    app(ProjectAccessService::class)->grantRole($project, $role, $actor, ['projects.view']);

    $this->actingAs($allowedUser)
        ->get(route('projects.show', $project))
        ->assertSuccessful();

    $this->actingAs($blockedUser)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

it('shows assigned projects by default and supports broader permitted visibility filter', function (): void {
    $user = userWithAccessPermissions(['projects.view']);

    $assignedProject = Project::factory()->create([
        'name' => 'Assigned Open Project',
        'project_manager_id' => $user->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $permittedProject = Project::factory()->create([
        'name' => 'Permitted Open Project',
        'project_manager_id' => null,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertSuccessful()
        ->assertSee($assignedProject->name)
        ->assertDontSee($permittedProject->name);

    $this->actingAs($user)
        ->get(route('projects.index', ['visibility' => 'permitted']))
        ->assertSuccessful()
        ->assertSee($assignedProject->name)
        ->assertSee($permittedProject->name);
});

it('hides scoped projects from broader permitted visibility when user has no scoped assignment', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $currentUser = userWithAccessPermissions(['projects.view']);
    $otherUser = userWithAccessPermissions(['projects.view']);

    $scopedProject = Project::factory()->create([
        'name' => 'Scoped Hidden Project',
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    app(ProjectAccessService::class)->grant($scopedProject, $otherUser, $actor, ['projects.view']);

    $this->actingAs($currentUser)
        ->get(route('projects.index', ['visibility' => 'permitted']))
        ->assertSuccessful()
        ->assertDontSee('Scoped Hidden Project');
});

it('enforces scoped edit permission on admin project edit route when project access is scoped', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'projects.edit', 'project-access.grant']);
    $restrictedEditor = userWithAccessPermissions(['projects.view', 'projects.edit']);
    $project = Project::factory()->create();

    app(ProjectAccessService::class)->grant($project, $restrictedEditor, $actor, ['projects.view']);

    $this->actingAs($restrictedEditor)
        ->get(route('admin.projects.edit', $project))
        ->assertForbidden();

    app(ProjectAccessService::class)->grant($project, $restrictedEditor, $actor, ['projects.view', 'projects.edit']);

    $this->actingAs($restrictedEditor)
        ->get(route('admin.projects.edit', $project))
        ->assertSuccessful();
});

it('checks scoped permission keys through service', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $member = userWithAccessPermissions(['projects.view']);
    $project = Project::factory()->create();

    $service = app(ProjectAccessService::class);
    $service->grant($project, $member, $actor, ['projects.view']);

    expect($service->hasScopedPermission($project, $member, 'projects.view'))->toBeTrue()
        ->and($service->hasScopedPermission($project, $member, 'projects.edit'))->toBeFalse();
});

it('dispatches a granted notification to the assignee when access is granted', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant']);
    $assignee = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    app(ProjectAccessService::class)->grant($project, $assignee, $actor, ['projects.view']);

    Notification::assertSentTo($assignee, ProjectAccessGrantedNotification::class, function (ProjectAccessGrantedNotification $notification) use ($project): bool {
        return $notification->project->id === $project->id;
    });
});

it('stores and revokes role-based project access assignments', function (): void {
    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant', 'project-access.revoke']);
    $project = Project::factory()->create();

    $role = Role::query()->create([
        'name' => 'Project Role Assignment '.str()->uuid(),
        'description' => 'Role assignment for project access service test',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    app(ProjectAccessService::class)->grantRole($project, $role, $actor, ['projects.view', 'projects.edit']);

    expect(ProjectRoleAccess::query()
        ->where('project_id', $project->id)
        ->where('role_id', $role->id)
        ->exists())->toBeTrue();

    app(ProjectAccessService::class)->revokeRole($project, $role, $actor);

    expect(ProjectRoleAccess::query()
        ->where('project_id', $project->id)
        ->where('role_id', $role->id)
        ->exists())->toBeFalse();
});

it('dispatches a revoked notification to the assignee when access is revoked', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $actor = userWithAccessPermissions(['projects.view', 'project-access.grant', 'project-access.revoke']);
    $assignee = User::factory()->create(['is_admin' => false]);
    $project = Project::factory()->create();

    app(ProjectAccessService::class)->grant($project, $assignee, $actor, ['projects.view']);
    app(ProjectAccessService::class)->revoke($project, $assignee, $actor);

    Notification::assertSentTo($assignee, ProjectAccessRevokedNotification::class, function (ProjectAccessRevokedNotification $notification) use ($project): bool {
        return $notification->project->id === $project->id;
    });
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
