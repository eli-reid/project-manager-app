<?php

namespace App\Domains\Plans\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Projects\Models\Project;

class PlanSheetPolicy
{
    public function view(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.view');
    }

    public function update(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.update');
    }

    public function delete(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.delete');
    }

    public function publishRevision(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.publish-revision');
    }

    public function compare(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.compare');
    }

    public function export(User $user, PlanSheet $sheet): bool
    {
        return $this->visible($user, $sheet, 'plans.export');
    }

    private function visible(User $user, PlanSheet $sheet, string $permission): bool
    {
        $project = Project::query()->find($sheet->project_id);

        return $project !== null && $user->hasPermission($permission)
            && $user->can('view', $project);
    }
}
