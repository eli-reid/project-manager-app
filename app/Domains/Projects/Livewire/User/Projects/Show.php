<?php

namespace App\Domains\Projects\Livewire\User\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Project Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project->loadMissing('address');
    }

    public function render()
    {
        return view('projects::livewire.user.projects.show');
    }
}
