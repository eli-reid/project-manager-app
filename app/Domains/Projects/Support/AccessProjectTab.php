<?php

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
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

    public function badgeCount(User $user, Project $project): ?int
    {
        return ProjectUserAccess::query()
            ->where('project_id', $project->id)
            ->count() + ProjectRoleAccess::query()
            ->where('project_id', $project->id)
            ->count();
    }
}
