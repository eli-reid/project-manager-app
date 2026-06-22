<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Projects')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function render()
    {
        $query = Project::query();

        // Leave is now recorded on timecard entries; no special ordering needed.

        return view('projects::livewire.admin.projects.index', [
            'projects' => $query
                ->latest()
                ->paginate(10),
        ]);
    }
}
