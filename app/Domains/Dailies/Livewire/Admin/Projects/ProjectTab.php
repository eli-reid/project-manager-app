<?php

namespace App\Domains\Dailies\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    public Project $project;

    public Collection $dailies;

    public int $dailyCount = 0;

    public string $projectDailiesUrl = '';

    public function mount(Project $project, Collection $dailies, int $dailyCount): void
    {
        $this->project = $project;
        $this->dailies = $dailies;
        $this->dailyCount = $dailyCount;
        $this->projectDailiesUrl = route('admin.projects.show', ['project' => $project, 'tab' => 'dailies']);
    }

    public function render()
    {
        return view('dailies::components.project-tab');
    }
}
