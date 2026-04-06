<?php

namespace App\Domains\Projects\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectUserAccess;

class ProjectAccessService
{
    public function grant(Project $project, User $user, User $actor, array $permissionKeys = []): ProjectUserAccess
    {
        $sanitizedPermissionKeys = collect($permissionKeys)
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();

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
    }

    public function hasAccess(Project $project, User $user): bool
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
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
