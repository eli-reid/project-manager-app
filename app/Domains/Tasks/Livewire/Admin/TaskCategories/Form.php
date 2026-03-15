<?php

namespace App\Domains\Tasks\Livewire\Admin\TaskCategories;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskDepthGuardService;
use App\Domains\Tasks\Services\TaskTreeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Task Category Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?TaskCategory $taskCategory = null;

    public bool $isEdit = false;

    public ?string $project_id = null;

    public ?string $parent_id = null;

    public string $name = '';

    public ?string $description = null;

    public int $sort_order = 0;

    public bool $is_active = true;

    public function mount(?TaskCategory $taskCategory = null): void
    {
        if ($taskCategory !== null && $taskCategory->exists) {
            $this->authorize('update', $taskCategory);

            $this->taskCategory = $taskCategory;
            $this->isEdit = true;
            $this->project_id = $taskCategory->project_id;
            $this->parent_id = $taskCategory->parent_id;
            $this->name = $taskCategory->name;
            $this->description = $taskCategory->description;
            $this->sort_order = (int) $taskCategory->sort_order;
            $this->is_active = (bool) $taskCategory->is_active;

            return;
        }

        $this->authorize('create', TaskCategory::class);
    }

    protected function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'parent_id' => [
                'nullable',
                'exists:task_categories,id',
                Rule::notIn(array_filter([$this->taskCategory?->id])),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(TaskDepthGuardService $depthGuard, TaskTreeService $treeService): void
    {
        $validated = $this->validate();

        $depthGuard->assertCategoryDepth($validated['parent_id'] ?? null);

        if ($this->isEdit) {
            if ($this->taskCategory === null) {
                return;
            }

            $this->authorize('update', $this->taskCategory);
            $this->taskCategory->update($validated);

            $treeService->clearCategoryTreeCache($this->taskCategory->project_id);

            session()->flash('success', 'Task category updated successfully.');
            $this->redirectRoute('admin.task-categories.index', navigate: true);

            return;
        }

        $this->authorize('create', TaskCategory::class);

        $category = TaskCategory::query()->create($validated);
        $treeService->clearCategoryTreeCache($category->project_id);

        session()->flash('success', 'Task category created successfully.');
        $this->redirectRoute('admin.task-categories.index', navigate: true);
    }

    public function render()
    {
        $query = TaskCategory::query()->where('is_active', true)->orderBy('name');

        if ($this->taskCategory !== null) {
            $query->whereKeyNot($this->taskCategory->id);
        }

        return view('tasks::livewire.admin.task-categories.form', [
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'parentCategories' => $query->get(['id', 'name', 'project_id']),
        ]);
    }
}
