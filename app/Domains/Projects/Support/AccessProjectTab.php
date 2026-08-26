<?php

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class AccessProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'access',
            label: 'Access',
            sort: 100,
            panel: new LivewireComponentTabPanel(
                component: 'projects::admin.projects.access-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->hasPermission('project-access.view')
            || $user->hasPermission('project-access.grant')
            || $user->hasPermission('project-access.revoke')
            || $user->hasPermission('project-access.manage');
    }

    public function badgeCountRelations(User $user, Project $project): array
    {
        return ['userAccesses', 'roleAccesses'];
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        $userAccessCount = $project->getAttribute('user_accesses_count');
        $roleAccessCount = $project->getAttribute('role_accesses_count');

        if (is_numeric($userAccessCount) && is_numeric($roleAccessCount)) {
            return (int) $userAccessCount + (int) $roleAccessCount;
        }

        return $project->userAccesses()->count() + $project->roleAccesses()->count();
    }
}
