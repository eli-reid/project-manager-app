<?php

namespace App\Domains\Tasks\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\Tasks\Models\Task;

final class TasksProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'tasks',
            label: 'Tasks',
            sort: 30,
            panel: new LivewireComponentTabPanel(
                component: 'tasks::admin.projects.task-hierarchy-widget',
                baseProps: [],
                keyPattern: 'project-task-widget-{projectId}-{taskWidgetVersion}',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->hasPermission('tasks.view')
            || $user->hasPermission('task-categories.view');
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->count();
    }
}
