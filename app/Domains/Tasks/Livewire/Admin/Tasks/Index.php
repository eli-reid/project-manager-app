<?php

namespace App\Domains\Tasks\Livewire\Admin\Tasks;

use App\Domains\Tasks\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Tasks')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?string $projectFilter = null;

    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Task::class);
    }

    public function deleteTask(string $taskId): void
    {
        $task = Task::query()->findOrFail($taskId);
        $this->authorize('delete', $task);

        $task->delete();

        session()->flash('success', 'Task deleted successfully.');
    }

    public function render()
    {
        $query = Task::query()
            ->with(['project:id,name', 'category:id,name', 'parentTask:id,title', 'assignedTo:id,first_name,last_name'])
            ->latest();

        if ($this->projectFilter !== null && $this->projectFilter !== '') {
            $query->where('project_id', $this->projectFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return view('tasks::livewire.admin.tasks.index', [
            'tasks' => $query->paginate(10),
            'statuses' => Task::statuses(),
        ]);
    }
}
