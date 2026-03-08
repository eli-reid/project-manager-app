<?php

namespace App\Core\Scheduler\Livewire\Admin\Tasks;

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Scheduler Tasks')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $featureType = '';

    public string $status = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', ScheduledTask::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFeatureType(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function toggleEnabled(string $taskId): void
    {
        $task = ScheduledTask::query()->findOrFail($taskId);
        $this->authorize('toggle', $task);

        app(ScheduledTaskService::class)->toggleTask($task);

        session()->flash('success', "Task '{$task->name}' status updated.");
    }

    public function runNow(string $taskId): void
    {
        $task = ScheduledTask::query()->findOrFail($taskId);
        $this->authorize('run', $task);

        ProcessScheduledTaskJob::dispatch((string) $task->id)->onQueue('scheduled-tasks');

        session()->flash('success', "Task '{$task->name}' queued for execution.");
    }

    public function deleteTask(string $taskId): void
    {
        $task = ScheduledTask::query()->findOrFail($taskId);
        $this->authorize('delete', $task);

        $name = $task->name;
        $task->delete();

        session()->flash('success', "Task '{$name}' deleted.");
    }

    public function render()
    {
        $tasks = ScheduledTask::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->featureType !== '', fn ($query) => $query->where('feature_type', $this->featureType))
            ->when($this->status !== 'all', function ($query): void {
                if ($this->status === 'active') {
                    $query->where('is_active', true)->where('is_enabled', true);
                }

                if ($this->status === 'disabled') {
                    $query->where('is_enabled', false);
                }
            })
            ->orderByDesc('is_enabled')
            ->orderBy('next_run_at')
            ->paginate(12);

        return view('scheduler::livewire.admin.tasks.index', [
            'tasks' => $tasks,
            'featureTypes' => array_keys(app(TaskTypeRegistry::class)->all()),
        ]);
    }
}
