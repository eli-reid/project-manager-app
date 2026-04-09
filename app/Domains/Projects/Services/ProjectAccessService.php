<?php

namespace App\Domains\Projects\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Notifications\ProjectAccessGrantedNotification;
use App\Domains\Projects\Notifications\ProjectAccessRevokedNotification;

class ProjectAccessService
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_PERMISSION_KEYS = [
        'projects.view',
        'projects.edit',
        'projects.delete',
    ];

    /**
     * @return array<string, string>
     */
    public function availablePermissionOptions(): array
    {
        return [
            'projects.view' => 'View project',
            'projects.edit' => 'Edit project',
            'projects.delete' => 'Delete project',
        ];
    }

    public function grant(Project $project, User $user, User $actor, array $permissionKeys = []): ProjectUserAccess
    {
        $sanitizedPermissionKeys = $this->sanitizePermissionKeys($permissionKeys);

        $existing = ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        $access = ProjectUserAccess::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            [
                'granted_by' => $actor->id,
                'permission_keys' => $sanitizedPermissionKeys,
            ]
        );

        app(AuditLogger::class)->record('project-access.grant', $project, [
            'before' => $existing ? $this->snapshot($existing) : null,
            'after' => $this->snapshot($access),
            'assignee_user_id' => (string) $user->id,
        ], $actor);

        $user->notify(new ProjectAccessGrantedNotification($project));

        return $access;
    }

    public function grantRole(Project $project, Role $role, User $actor, array $permissionKeys = []): ProjectRoleAccess
    {
        $sanitizedPermissionKeys = $this->sanitizePermissionKeys($permissionKeys);

        $existing = ProjectRoleAccess::query()
            ->where('project_id', $project->id)
            ->where('role_id', $role->id)
            ->first();

        $access = ProjectRoleAccess::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'role_id' => $role->id,
            ],
            [
                'granted_by' => $actor->id,
                'permission_keys' => $sanitizedPermissionKeys,
            ]
        );

        app(AuditLogger::class)->record('project-access.grant-role', $project, [
            'before' => $existing ? $this->roleSnapshot($existing) : null,
            'after' => $this->roleSnapshot($access),
            'assignee_role_id' => (string) $role->id,
        ], $actor);

        return $access;
    }

    public function revoke(Project $project, User $user, User $actor): void
    {
        $access = ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($access === null) {
            return;
        }

        $before = $this->snapshot($access);
        $access->delete();

        app(AuditLogger::class)->record('project-access.revoke', $project, [
            'before' => $before,
            'after' => null,
            'assignee_user_id' => (string) $user->id,
        ], $actor);

        $user->notify(new ProjectAccessRevokedNotification($project));
    }

    public function revokeRole(Project $project, Role $role, User $actor): void
    {
        $access = ProjectRoleAccess::query()
            ->where('project_id', $project->id)
            ->where('role_id', $role->id)
            ->first();

        if ($access === null) {
            return;
        }

        $before = $this->roleSnapshot($access);
        $access->delete();

        app(AuditLogger::class)->record('project-access.revoke-role', $project, [
            'before' => $before,
            'after' => null,
            'assignee_role_id' => (string) $role->id,
        ], $actor);
    }

    public function hasAccess(Project $project, User $user): bool
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function hasScopedPermission(Project $project, User $user, string $permissionKey): bool
    {
        $hasDirectPermission = ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->whereJsonContains('permission_keys', $permissionKey)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        $roleIds = $this->activeRoleIdsFor($user);
        if ($roleIds === []) {
            return false;
        }

        return ProjectRoleAccess::query()
            ->where('project_id', $project->id)
            ->whereIn('role_id', $roleIds)
            ->whereJsonContains('permission_keys', $permissionKey)
            ->exists();
    }

    public function projectUsesScopedAccess(Project $project): bool
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->exists()
            || ProjectRoleAccess::query()
                ->where('project_id', $project->id)
                ->exists();
    }

    /**
     * @param  array<int, mixed>  $permissionKeys
     * @return array<int, string>
     */
    private function sanitizePermissionKeys(array $permissionKeys): array
    {
        $sanitizedPermissionKeys = collect($permissionKeys)
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->filter(fn (string $permission): bool => in_array($permission, self::SUPPORTED_PERMISSION_KEYS, true))
            ->unique()
            ->values()
            ->all();

        if ($sanitizedPermissionKeys === []) {
            $sanitizedPermissionKeys = ['projects.view'];
        }

        if (! in_array('projects.view', $sanitizedPermissionKeys, true)) {
            $sanitizedPermissionKeys[] = 'projects.view';
        }

        return $sanitizedPermissionKeys;
    }

    /**
     * @return array<int, string>
     */
    private function activeRoleIdsFor(User $user): array
    {
        return $user->roles()
            ->where('is_active', true)
            ->pluck('roles.id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ProjectUserAccess $access): array
    {
        return [
            'project_id' => (string) $access->project_id,
            'user_id' => (string) $access->user_id,
            'granted_by' => $access->granted_by !== null ? (string) $access->granted_by : null,
            'permission_keys' => collect($access->permission_keys ?? [])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function roleSnapshot(ProjectRoleAccess $access): array
    {
        return [
            'project_id' => (string) $access->project_id,
            'role_id' => (string) $access->role_id,
            'granted_by' => $access->granted_by !== null ? (string) $access->granted_by : null,
            'permission_keys' => collect($access->permission_keys ?? [])->values()->all(),
        ];
    }
}
