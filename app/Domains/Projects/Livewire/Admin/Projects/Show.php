<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Project Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        if (! in_array($this->activeTab, $this->tabs(), true)) {
            $this->activeTab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, $this->tabs(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * @return array<int, string>
     */
    protected function tabs(): array
    {
        $tabs = ['overview'];
        $user = Auth::user();

        if ($user?->hasPermission('tasks.view') || $user?->hasPermission('task-categories.view')) {
            $tabs[] = 'tasks';
        }

        return $tabs;
    }

    public function render()
    {
        return view('projects::livewire.admin.projects.show', [
            'tabs' => $this->tabs(),
            'taskCount' => Task::query()->where('project_id', $this->project->id)->count(),
        ]);
    }
}
