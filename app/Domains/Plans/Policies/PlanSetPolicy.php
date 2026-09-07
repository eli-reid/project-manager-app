<?php

namespace App\Domains\Plans\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Projects\Models\Project;

class PlanSetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('plans.view');
    }

    public function view(User $user, PlanSet $planSet): bool
    {
        return $this->projectVisible($user, $planSet->project_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('plans.upload');
    }

    public function update(User $user, PlanSet $planSet): bool
    {
        return $this->projectCan($user, $planSet->project_id, 'plans.update');
    }

    public function delete(User $user, PlanSet $planSet): bool
    {
        return $this->projectCan($user, $planSet->project_id, 'plans.delete');
    }

    private function projectVisible(User $user, string $projectId): bool
    {
        $project = Project::query()->find($projectId);

        return $project !== null && $user->hasPermission('plans.view') && $user->can('view', $project);
    }

    private function projectCan(User $user, string $projectId, string $permission): bool
    {
        return $user->hasPermission($permission) && $this->projectVisible($user, $projectId);
    }
}
