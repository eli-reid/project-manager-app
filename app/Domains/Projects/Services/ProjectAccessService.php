<?php

namespace App\Domains\Projects\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
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

    public function hasAccess(Project $project, User $user): bool
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function hasScopedPermission(Project $project, User $user, string $permissionKey): bool
    {
        $access = ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($access === null) {
            return false;
        }

        $permissionKeys = collect($access->permission_keys ?? [])
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();

        return in_array($permissionKey, $permissionKeys, true);
    }

    public function projectUsesScopedAccess(Project $project): bool
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->exists();
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
}
