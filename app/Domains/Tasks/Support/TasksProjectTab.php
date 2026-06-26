<?php

namespace App\Domains\Tasks\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\AbstractProjectTab;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Livewire\Admin\Tasks\Index as TasksIndex;



final readonly class TasksProjectTab extends AbstractProjectTab   
{
    public function __construct()
    {
        parent::__construct(
            key: 'tasks',
            label: 'Tasks',
            sort: 30,
            //modeParam: 'mode',
            detailQueryParam: 'detail',
            panel: new LivewireComponentTabPanel(
                component: TasksIndex::class,
                baseProps: ['embedded' => true],
                detailView: [
                    'component' => Null, // To be implemented: Task detail component
                    'baseProps' => ['embedded' => true],
                    'detailProp' => 'task',
                    'keyPattern' => 'project-tasks-show-{projectId}-{detailId}',
                    'viewStateProps' => ['returnTo' => 'returnTo'],
                ],
                keyPattern: 'project-tasks-index-{projectId}',
            ),
        );
    }

    public function modeQueryParam(): ?string
    {
        return 'mode';
    }

    public function detailQueryParam(): ?string
    {
        return 'detail';
    }

    public function panel(): LivewireComponentTabPanel
    {
        return $this->panel;
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
