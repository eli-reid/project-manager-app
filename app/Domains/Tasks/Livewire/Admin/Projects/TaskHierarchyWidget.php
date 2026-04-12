<?php

namespace App\Domains\Tasks\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\ProjectTaskHierarchyViewDataService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TaskHierarchyWidget extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public ?string $copySourceCategoryId = null;

    public ?string $copyTargetCategoryId = null;

    public ?string $copyCategorySourceId = null;

    public bool $copyIncludeSubtasks = true;

    public bool $showInlineCategoryForm = false;

    public bool $showInlineTaskForm = false;

    public string $inlineCategoryName = '';

    public string $inlineCategoryDescription = '';

    public ?string $inlineCategoryParentId = null;

    public string $inlineTaskTitle = '';

    public string $inlineTaskDescription = '';

    public ?string $inlineTaskCategoryId = null;

    public ?string $inlineTaskAssignedTo = null;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function copyCategoryTasks(): void
    {
        $this->authorize('create', Task::class);

        $validated = $this->validate([
            'copySourceCategoryId' => [
                'required',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'copyTargetCategoryId' => [
                'nullable',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'copyIncludeSubtasks' => ['boolean'],
        ]);

        if ($validated['copySourceCategoryId'] === $validated['copyTargetCategoryId']) {
            $this->addError('copyTargetCategoryId', 'Target category must be different from source category.');

            return;
        }

        $sourceCategory = TaskCategory::query()->findOrFail($validated['copySourceCategoryId']);

        /** @var EloquentCollection<int, Task> $sourceTasks */
        $sourceTasks = Task::query()
            ->where('project_id', $this->project->id)
            ->where('task_category_id', $sourceCategory->id)
            ->whereNull('parent_task_id')
            ->with(['subTasks'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $copiedCount = 0;

        DB::transaction(function () use ($sourceTasks, $validated, &$copiedCount): void {
            foreach ($sourceTasks as $sourceTask) {
                $taskCopy = Task::query()->create([
                    'project_id' => $this->project->id,
                    'task_category_id' => $validated['copyTargetCategoryId'] ?: null,
                    'parent_task_id' => null,
                    'title' => $sourceTask->title.' (Copy)',
                    'description' => $sourceTask->description,
                    'status' => $sourceTask->status,
                    'priority' => $sourceTask->priority,
                    'estimated_hours' => $sourceTask->estimated_hours,
                    'completion_percentage' => 0,
                    'due_date' => $sourceTask->due_date,
                    'assigned_to' => $sourceTask->assigned_to,
                    'is_billable' => $sourceTask->is_billable,
                    'sort_order' => $sourceTask->sort_order,
                ]);

                $copiedCount++;

                if (! $validated['copyIncludeSubtasks']) {
                    continue;
                }

                foreach ($sourceTask->subTasks as $sourceSubTask) {
                    Task::query()->create([
                        'project_id' => $this->project->id,
                        'task_category_id' => $validated['copyTargetCategoryId'] ?: null,
                        'parent_task_id' => $taskCopy->id,
                        'title' => $sourceSubTask->title.' (Copy)',
                        'description' => $sourceSubTask->description,
                        'status' => $sourceSubTask->status,
                        'priority' => $sourceSubTask->priority,
                        'estimated_hours' => $sourceSubTask->estimated_hours,
                        'completion_percentage' => 0,
                        'due_date' => $sourceSubTask->due_date,
                        'assigned_to' => $sourceSubTask->assigned_to,
                        'is_billable' => $sourceSubTask->is_billable,
                        'sort_order' => $sourceSubTask->sort_order,
                    ]);

                    $copiedCount++;
                }
            }
        });

        $this->reset('copySourceCategoryId', 'copyTargetCategoryId', 'copyIncludeSubtasks');
        $this->copyIncludeSubtasks = true;

        session()->flash('success', "Copied {$copiedCount} tasks from {$sourceCategory->name}.");
    }

    public function copyCategoryFrom(string $categoryId): void
    {
        $this->copyCategorySourceId = $categoryId;
        $this->copyCategory();
    }

    public function deleteCategory(string $categoryId): void
    {
        $category = TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->find($categoryId);

        if (! $category) {
            session()->flash('error', 'Category no longer exists.');

            return;
        }

        $this->authorize('delete', $category);

        $branchCategoryIds = array_values(array_unique([
            (string) $category->id,
            ...$this->descendantCategoryIds($category),
        ]));

        $categoryName = $category->name;

        DB::transaction(function () use ($branchCategoryIds): void {
            Task::query()
                ->where('project_id', $this->project->id)
                ->whereIn('task_category_id', $branchCategoryIds)
                ->delete();

            TaskCategory::query()
                ->where('project_id', $this->project->id)
                ->whereIn('id', $branchCategoryIds)
                ->delete();
        });

        session()->flash('success', "Deleted category branch for {$categoryName}.");
    }

    public function copyTaskFrom(string $taskId): void
    {
        $this->authorize('create', Task::class);

        $sourceTask = Task::query()
            ->where('project_id', $this->project->id)
            ->with(['subTasks'])
            ->findOrFail($taskId);

        DB::transaction(function () use ($sourceTask): void {
            $taskCopy = Task::query()->create([
                'project_id' => $this->project->id,
                'task_category_id' => $sourceTask->task_category_id,
                'parent_task_id' => $sourceTask->parent_task_id,
                'title' => $this->nextTaskCopyTitle($sourceTask->title),
                'description' => $sourceTask->description,
                'status' => $sourceTask->status,
                'priority' => $sourceTask->priority,
                'estimated_hours' => $sourceTask->estimated_hours,
                'completion_percentage' => 0,
                'due_date' => $sourceTask->due_date,
                'assigned_to' => $sourceTask->assigned_to,
                'is_billable' => $sourceTask->is_billable,
                'sort_order' => $sourceTask->sort_order,
            ]);

            foreach ($sourceTask->subTasks as $sourceSubTask) {
                Task::query()->create([
                    'project_id' => $this->project->id,
                    'task_category_id' => $sourceSubTask->task_category_id,
                    'parent_task_id' => $taskCopy->id,
                    'title' => $this->nextTaskCopyTitle($sourceSubTask->title),
                    'description' => $sourceSubTask->description,
                    'status' => $sourceSubTask->status,
                    'priority' => $sourceSubTask->priority,
                    'estimated_hours' => $sourceSubTask->estimated_hours,
                    'completion_percentage' => 0,
                    'due_date' => $sourceSubTask->due_date,
                    'assigned_to' => $sourceSubTask->assigned_to,
                    'is_billable' => $sourceSubTask->is_billable,
                    'sort_order' => $sourceSubTask->sort_order,
                ]);
            }
        });

        session()->flash('success', "Copied task {$sourceTask->title}.");
    }

    public function deleteTask(string $taskId): void
    {
        $task = Task::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($taskId);

        $this->authorize('delete', $task);

        $hasSubTasks = Task::query()->where('parent_task_id', $task->id)->exists();
        if ($hasSubTasks) {
            session()->flash('error', 'Task cannot be deleted while it has subtasks.');

            return;
        }

        $taskTitle = $task->title;
        $task->delete();

        session()->flash('success', "Deleted task {$taskTitle}.");
    }

    public function copyCategory(): void
    {
        $this->authorize('create', TaskCategory::class);

        $validated = $this->validate([
            'copyCategorySourceId' => [
                'required',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
        ]);

        $sourceCategory = TaskCategory::query()->findOrFail($validated['copyCategorySourceId']);
        $copyName = $this->nextCategoryCopyName($sourceCategory->name);

        TaskCategory::query()->create([
            'project_id' => $this->project->id,
            'parent_id' => $sourceCategory->parent_id,
            'name' => $copyName,
            'description' => $sourceCategory->description,
            'sort_order' => $sourceCategory->sort_order,
            'is_active' => $sourceCategory->is_active,
        ]);

        $this->reset('copyCategorySourceId');

        session()->flash('success', "Copied category {$sourceCategory->name} to {$copyName}.");
    }

    public function startInlineCategoryForm(?string $parentCategoryId = null): void
    {
        $this->authorize('create', TaskCategory::class);

        $this->showInlineCategoryForm = true;
        $this->inlineCategoryParentId = $parentCategoryId;
    }

    public function cancelInlineCategoryForm(): void
    {
        $this->reset('inlineCategoryName', 'inlineCategoryDescription', 'inlineCategoryParentId');
        $this->showInlineCategoryForm = false;
    }

    public function createInlineCategory(): void
    {
        $this->authorize('create', TaskCategory::class);

        $validated = $this->validate([
            'inlineCategoryName' => ['required', 'string', 'max:255'],
            'inlineCategoryDescription' => ['nullable', 'string'],
            'inlineCategoryParentId' => [
                'nullable',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
        ]);

        TaskCategory::query()->create([
            'project_id' => $this->project->id,
            'parent_id' => $validated['inlineCategoryParentId'] ?: null,
            'name' => $validated['inlineCategoryName'],
            'description' => $validated['inlineCategoryDescription'] ?: null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->cancelInlineCategoryForm();

        session()->flash('success', 'Category created successfully.');
    }

    public function startInlineTaskForm(?string $categoryId = null): void
    {
        $this->authorize('create', Task::class);

        $this->showInlineTaskForm = true;
        $this->inlineTaskCategoryId = $categoryId;
    }

    public function cancelInlineTaskForm(): void
    {
        $this->reset('inlineTaskTitle', 'inlineTaskDescription', 'inlineTaskCategoryId', 'inlineTaskAssignedTo');
        $this->showInlineTaskForm = false;
    }

    public function createInlineTask(): void
    {
        $this->authorize('create', Task::class);

        $validated = $this->validate([
            'inlineTaskTitle' => ['required', 'string', 'max:255'],
            'inlineTaskDescription' => ['nullable', 'string'],
            'inlineTaskCategoryId' => [
                'required',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'inlineTaskAssignedTo' => ['nullable', 'exists:users,id'],
        ]);

        Task::query()->create([
            'project_id' => $this->project->id,
            'task_category_id' => $validated['inlineTaskCategoryId'],
            'parent_task_id' => null,
            'title' => $validated['inlineTaskTitle'],
            'description' => $validated['inlineTaskDescription'] ?: null,
            'status' => Task::STATUS_TODO,
            'priority' => Task::PRIORITY_MEDIUM,
            'completion_percentage' => 0,
            'assigned_to' => $validated['inlineTaskAssignedTo'] ?: null,
            'is_billable' => false,
            'sort_order' => 0,
        ]);

        $this->cancelInlineTaskForm();

        session()->flash('success', 'Task created successfully.');
    }

    public function render()
    {
        return view('tasks::livewire.admin.projects.task-hierarchy-widget', [
            'project' => $this->project,
            ...app(ProjectTaskHierarchyViewDataService::class)->forProject($this->project),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function descendantCategoryIds(mixed $category): array
    {
        $descendantIds = [];
        $children = $category->childrenRecursive ?? collect();

        foreach ($children as $child) {
            $descendantIds[] = (string) $child->id;
            $descendantIds = array_merge($descendantIds, $this->descendantCategoryIds($child));
        }

        return $descendantIds;
    }

    protected function nextCategoryCopyName(string $sourceName): string
    {
        $baseName = $sourceName.' (Copy)';
        $candidate = $baseName;
        $counter = 2;

        while (TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->where('name', $candidate)
            ->exists()) {
            $candidate = $baseName.' '.$counter;
            $counter++;
        }

        return $candidate;
    }

    protected function nextTaskCopyTitle(string $sourceTitle): string
    {
        $baseTitle = $sourceTitle.' (Copy)';
        $candidate = $baseTitle;
        $counter = 2;

        while (Task::query()
            ->where('project_id', $this->project->id)
            ->where('title', $candidate)
            ->exists()) {
            $candidate = $baseTitle.' '.$counter;
            $counter++;
        }

        return $candidate;
    }
}
