<?php

namespace App\Domains\Tasks\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Projects\Support\ProjectTab;

class TasksProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'tasks',
                label: 'Tasks',
                sort: 30,
                modeParam: null,
                detailQueryParam: null,
                panel: null,
                badgeResolver: static fn (User $user, Project $project): ?int => Task::query()
                    ->where('project_id', $project->id)
                    ->count(),
                visibilityResolver: static fn (User $user, Project $project): bool => $user->hasPermission('tasks.view')
                    || $user->hasPermission('task-categories.view'),
            ),
        ];
    }
}
