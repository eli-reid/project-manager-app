<?php

namespace App\Domains\Tasks\Livewire\User\Tasks;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Tasks')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public string $projectFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Task::class);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $query = Task::query()
            ->with(['project:id,name,project_number', 'category:id,name', 'assignedTo:id,first_name,last_name'])
            ->latest();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.edit')) {
            $query->where('assigned_to', $user->id);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter !== '') {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->projectFilter !== '') {
            $query->where('project_id', $this->projectFilter);
        }

        return view('tasks::livewire.user.tasks.index', [
            'tasks' => $query->paginate(10),
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'project_number']),
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
        ]);
    }
}
