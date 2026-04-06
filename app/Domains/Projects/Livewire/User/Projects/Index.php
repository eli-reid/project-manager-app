<?php

namespace App\Domains\Projects\Livewire\User\Projects;

use App\Domains\Projects\Enums\ProjectStatusEnum;
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

    public string $search = '';

    public bool $includeClosed = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedIncludeClosed(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $closedStatuses = [
            ProjectStatusEnum::COMPLETED->value,
            ProjectStatusEnum::FINAL_INSPECTION->value,
            ProjectStatusEnum::CANCELLED->value,
            ProjectStatusEnum::ARCHIVED->value,
        ];

        $projects = Project::query()
            ->with(['client:id,name', 'projectManager:id,first_name,last_name'])
            ->when(! $this->includeClosed, function ($query) use ($closedStatuses): void {
                $query->where('is_active', true)
                    ->whereNotIn('status', $closedStatuses);
            })
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', $search)
                        ->orWhere('project_number', 'like', $search);
                });
            })
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->paginate(10);

        return view('projects::livewire.user.projects.index', [
            'projects' => $projects,
        ]);
    }
}
