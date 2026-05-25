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
        return view('projects::livewire.admin.projects.index', [
            'projects' => Project::query()
                ->orderByRaw('CASE WHEN leave_category IS NOT NULL THEN 0 ELSE 1 END')
                ->latest()
                ->paginate(10),
        ]);
    }
}
