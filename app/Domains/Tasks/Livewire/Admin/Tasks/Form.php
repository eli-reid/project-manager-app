<?php

namespace App\Domains\Tasks\Livewire\Admin\Tasks;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskDepthGuardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Task Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Task $task = null;

    public bool $isEdit = false;

    public string $title = '';

    public ?string $description = null;

    public ?string $project_id = null;

    public ?string $task_category_id = null;

    public ?string $parent_task_id = null;

    public string $status = Task::STATUS_TODO;

    public string $priority = Task::PRIORITY_MEDIUM;

    public ?string $estimated_hours = null;

    public int $completion_percentage = 0;

    public ?string $due_date = null;

    public ?string $assigned_to = null;

    public bool $is_billable = true;

    public int $sort_order = 0;

    public function mount(?Task $task = null): void
    {
        if ($task !== null && $task->exists) {
            $this->authorize('update', $task);

            $this->task = $task;
            $this->isEdit = true;
            $this->title = $task->title;
            $this->description = $task->description;
            $this->project_id = $task->project_id;
            $this->task_category_id = $task->task_category_id;
            $this->parent_task_id = $task->parent_task_id;
            $this->status = $task->status;
            $this->priority = $task->priority;
            $this->estimated_hours = $task->estimated_hours !== null ? (string) $task->estimated_hours : null;
            $this->completion_percentage = (int) $task->completion_percentage;
            $this->due_date = $task->due_date?->format('Y-m-d');
            $this->assigned_to = $task->assigned_to;
            $this->is_billable = (bool) $task->is_billable;
            $this->sort_order = (int) $task->sort_order;

            return;
        }

        $this->authorize('create', Task::class);

        $requestedProjectId = request()->string('project_id')->value();
        if ($requestedProjectId !== '') {
            $this->project_id = $requestedProjectId;
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'exists:projects,id'],
            'task_category_id' => [
                'required',
                Rule::exists('task_categories', 'id')->where(function ($query): void {
                    $query->where('project_id', $this->project_id);
                }),
            ],
            'parent_task_id' => [
                'nullable',
                Rule::exists('tasks', 'id')->where(function ($query): void {
                    $query->where('project_id', $this->project_id);
                }),
                Rule::notIn(array_filter([$this->task?->id])),
            ],
            'status' => ['required', Rule::in(Task::statuses())],
            'priority' => ['required', Rule::in(Task::priorities())],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'is_billable' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function save(TaskDepthGuardService $depthGuard): void
    {
        $validated = $this->validate();

        $depthGuard->assertTaskDepth($validated['parent_task_id'] ?? null);
        $depthGuard->assertCombinedDepth($validated['task_category_id'] ?? null, $validated['parent_task_id'] ?? null);

        if ($this->isEdit) {
            if ($this->task === null) {
                return;
            }

            $this->authorize('update', $this->task);
            $this->task->update($validated);

            session()->flash('success', 'Task updated successfully.');
            $this->redirectRoute('admin.tasks.index', ['project_id' => $validated['project_id']], navigate: true);

            return;
        }

        $this->authorize('create', Task::class);
        Task::query()->create($validated);

        session()->flash('success', 'Task created successfully.');
        $this->redirectRoute('admin.tasks.index', ['project_id' => $validated['project_id']], navigate: true);
    }

    public function render()
    {
        $projectId = $this->project_id;

        return view('tasks::livewire.admin.tasks.form', [
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => TaskCategory::query()
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('name')
                ->get(['id', 'name', 'project_id']),
            'parentTasks' => Task::query()
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId), fn ($query) => $query->whereRaw('1 = 0'))
                ->when($this->task !== null, fn ($query) => $query->whereKeyNot($this->task->id))
                ->orderBy('title')
                ->get(['id', 'title', 'project_id']),
            'users' => User::query()->where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
        ]);
    }
}
