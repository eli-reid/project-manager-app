<?php

namespace App\Domains\Submittals\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    public Project $project;

    public Collection $submittals;

    public int $submittalCount = 0;

    public function mount(Project $project, Collection $submittals, int $submittalCount): void
    {
        $this->project = $project;
        $this->submittals = $submittals;
        $this->submittalCount = $submittalCount;
    }

    public function render()
    {
        return view('submittals::components.project-tab');
    }
}
