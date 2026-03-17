<?php

namespace App\Domains\Tasks\Livewire\Admin\TaskTemplates;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Task Template Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?TaskTemplate $taskTemplate = null;

    public bool $isEdit = false;

    public string $name = '';

    public ?string $description = null;

    public ?string $task_category_id = null;

    public string $priority = Task::PRIORITY_MEDIUM;

    public ?string $estimated_hours = null;

    public bool $is_billable = true;

    /** @var array<int, array<string, mixed>> */
    public array $template_tasks = [];

    public bool $is_active = true;

    public function mount(?TaskTemplate $taskTemplate = null): void
    {
        if ($taskTemplate !== null && $taskTemplate->exists) {
            $this->authorize('update', $taskTemplate);

            $this->taskTemplate = $taskTemplate;
            $this->isEdit = true;
            $this->name = $taskTemplate->name;
            $this->description = $taskTemplate->description;
            $this->task_category_id = $taskTemplate->task_category_id;
            $this->priority = $taskTemplate->priority;
            $this->estimated_hours = $taskTemplate->estimated_hours !== null ? (string) $taskTemplate->estimated_hours : null;
            $this->is_billable = (bool) $taskTemplate->is_billable;
            $this->template_tasks = $taskTemplate->template_tasks ?? [];
            $this->is_active = (bool) $taskTemplate->is_active;

            return;
        }

        $this->authorize('create', TaskTemplate::class);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_category_id' => ['nullable', 'exists:task_categories,id'],
            'priority' => ['required', Rule::in(Task::priorities())],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'is_billable' => ['boolean'],
            'template_tasks' => ['array'],
            'template_tasks.*.title' => ['required', 'string', 'max:255'],
            'template_tasks.*.priority' => ['required', Rule::in(Task::priorities())],
            'template_tasks.*.estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function addTemplateTask(): void
    {
        $this->template_tasks[] = [
            'title' => '',
            'priority' => Task::PRIORITY_MEDIUM,
            'estimated_hours' => null,
        ];
    }

    public function removeTemplateTask(int $index): void
    {
        array_splice($this->template_tasks, $index, 1);
        $this->template_tasks = array_values($this->template_tasks);
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            if ($this->taskTemplate === null) {
                return;
            }

            $this->authorize('update', $this->taskTemplate);
            $this->taskTemplate->update($validated);

            session()->flash('success', 'Task template updated successfully.');
            $this->redirectRoute('admin.task-templates.index', navigate: true);

            return;
        }

        $this->authorize('create', TaskTemplate::class);

        TaskTemplate::query()->create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Task template created successfully.');
        $this->redirectRoute('admin.task-templates.index', navigate: true);
    }

    public function render()
    {
        return view('tasks::livewire.admin.task-templates.form', [
            'categories' => TaskCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => Task::priorities(),
        ]);
    }
}
