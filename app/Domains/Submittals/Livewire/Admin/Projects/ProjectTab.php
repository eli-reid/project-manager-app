<?php

namespace App\Domains\Submittals\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    public Project $project;

    public Collection $submittals;

    public int $submittalCount = 0;

    public string $projectSubmittalsUrl = '';

    public string $submittalCreateUrl = '';

    public string $reviewSubmittalId = '';

    public bool $isCreateMode = false;

    public bool $isReviewMode = false;

    public ?Submittal $reviewSubmittal = null;

    public function mount(Project $project, Collection $submittals, int $submittalCount): void
    {
        $this->project = $project;
        $this->submittals = $submittals;
        $this->submittalCount = $submittalCount;

        $this->projectSubmittalsUrl = route('admin.projects.show', ['project' => $project, 'tab' => 'submittals'], false);
        $this->submittalCreateUrl = route('admin.projects.show', ['project' => $project, 'tab' => 'submittals', 'submittalMode' => 'create']);
        $this->reviewSubmittalId = (string) request()->query('submittalId', '');
        $this->isCreateMode = request()->query('submittalMode') === 'create';
        $this->isReviewMode = request()->query('submittalMode') === 'review' && $this->reviewSubmittalId !== '';
        $this->reviewSubmittal = $this->isReviewMode
            ? $this->submittals->firstWhere('id', $this->reviewSubmittalId)
            : null;
    }

    public function render()
    {
        return view('submittals::components.project-tab');
    }
}
