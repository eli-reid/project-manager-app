<?php

namespace App\Domains\Tasks\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use App\Domains\Tasks\Services\ProjectTaskHierarchyViewDataService;
use App\Domains\Tasks\Services\TaskTreeService;
use App\Domains\Tasks\Support\TaskBatchTitleGenerator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
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

    public ?string $copyCategoryDestinationParentId = null;

    public int $copyCategoryQuantity = 1;

    public string $copyCategoryNamePrefix = '';

    public ?int $copyCategoryStartNumber = null;

    public ?string $saveTemplateSourceCategoryId = null;

    public string $saveTemplateName = '';

    public ?string $saveTemplateDescription = null;

    public bool $copyIncludeSubtasks = true;

    public bool $copyIncludeChildCategories = false;

    public bool $copyIncludeCategoryTasks = false;

    public ?string $copyTaskSourceId = null;

    public bool $copyIncludeSubtasksOnTask = true;

    public bool $showInlineCategoryForm = false;

    public bool $showInlineTaskForm = false;

    public string $inlineCategoryName = '';

    public string $inlineCategoryDescription = '';

    public ?string $inlineCategoryParentId = null;

    public int $inlineCategoryBatchCount = 1;

    public int $inlineCategoryBatchStartNumber = 1;

    public string $inlineTaskTitle = '';

    public string $inlineTaskDescription = '';

    public ?string $inlineTaskCategoryId = null;

    public ?string $inlineTaskAssignedTo = null;

    public int $inlineTaskBatchCount = 1;

    public int $inlineTaskBatchStartNumber = 1;

    public ?string $editingTaskStatus = null;

    public ?string $editingTaskStatusValue = null;

    public ?string $editingTaskPriority = null;

    public ?string $editingTaskPriorityValue = null;

    public ?string $editingTaskTitle = null;

    public string $editingTaskTitleValue = '';

    public ?string $editingCategoryName = null;

    public string $editingCategoryNameValue = '';

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

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', "Copied {$copiedCount} tasks from {$sourceCategory->name}.");
    }

    public function copyCategoryFrom(?string $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $this->copyCategorySourceId = $categoryId;
        $this->dispatch('open-copy-category-modal');
    }

    public function startSaveCategoryAsTemplate(?string $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $this->authorize('create', TaskTemplate::class);

        $category = TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($categoryId);

        $this->saveTemplateSourceCategoryId = (string) $category->id;
        $this->saveTemplateName = $this->nextTemplateName($category->name.' Template');
        $this->saveTemplateDescription = "Template created from project category: {$category->name}";

        $this->dispatch('open-save-template-modal');
    }

    public function saveCategoryAsTemplate(): void
    {
        $this->authorize('create', TaskTemplate::class);

        $validated = $this->validate([
            'saveTemplateSourceCategoryId' => [
                'required',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'saveTemplateName' => ['required', 'string', 'max:255'],
            'saveTemplateDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($validated['saveTemplateSourceCategoryId']);

        $templateTasks = $this->buildTemplateTasksForCategoryBranch($category);

        TaskTemplate::query()->create([
            'name' => $validated['saveTemplateName'],
            'description' => $validated['saveTemplateDescription'],
            'task_category_id' => $category->id,
            'priority' => Task::PRIORITY_MEDIUM,
            'estimated_hours' => null,
            'is_billable' => false,
            'template_tasks' => $templateTasks,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        $this->reset('saveTemplateSourceCategoryId', 'saveTemplateName', 'saveTemplateDescription');
        $this->dispatch('close-save-template-modal');

        session()->flash('success', "Saved category {$category->name} as a task template.");
    }

    public function deleteCategory(?string $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

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

        // Mass deletes bypass Eloquent observers, so clear the cache manually.
        app(TaskTreeService::class)->clearCategoryTreeCache($this->project->id);

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', "Deleted category branch for {$categoryName}.");
    }

    public function copyTaskFrom(?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

        $this->authorize('create', Task::class);

        $this->copyTaskSourceId = $taskId;
        $this->dispatch('open-copy-task-modal');
    }

    public function copyTask(): void
    {
        $this->authorize('create', Task::class);

        $validated = $this->validate([
            'copyTaskSourceId' => [
                'required',
                Rule::exists('tasks', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'copyIncludeSubtasksOnTask' => ['boolean'],
        ]);

        $sourceTask = Task::query()
            ->where('project_id', $this->project->id)
            ->with(['subTasks'])
            ->findOrFail($validated['copyTaskSourceId']);

        DB::transaction(function () use ($sourceTask, $validated): void {
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

            if (! $validated['copyIncludeSubtasksOnTask']) {
                return;
            }

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

        $this->reset('copyTaskSourceId', 'copyIncludeSubtasksOnTask');
        $this->copyIncludeSubtasksOnTask = true;

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', "Copied task {$sourceTask->title}.");
    }

    public function deleteTask(?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

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

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', "Deleted task {$taskTitle}.");
    }

    public function moveTask(?string $taskId, string $direction): void
    {
        if (! filled($taskId)) {
            return;
        }

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $task = Task::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($taskId);

        $this->authorize('update', $task);

        $siblingIds = Task::query()
            ->where('project_id', $this->project->id)
            ->where('task_category_id', $task->task_category_id)
            ->where('parent_task_id', $task->parent_task_id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $currentIndex = array_search($task->id, $siblingIds, true);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if (! isset($siblingIds[$targetIndex])) {
            return;
        }

        $movedId = $siblingIds[$currentIndex];
        $siblingIds[$currentIndex] = $siblingIds[$targetIndex];
        $siblingIds[$targetIndex] = $movedId;

        DB::transaction(function () use ($siblingIds): void {
            foreach ($siblingIds as $index => $siblingId) {
                Task::query()
                    ->where('project_id', $this->project->id)
                    ->where('id', $siblingId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Task order updated.');
    }

    public function moveCategory(?string $categoryId, string $direction): void
    {
        if (! filled($categoryId)) {
            return;
        }

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $category = TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($categoryId);

        $this->authorize('update', $category);

        $siblingIds = TaskCategory::query()
            ->where('project_id', $this->project->id)
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $currentIndex = array_search($category->id, $siblingIds, true);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if (! isset($siblingIds[$targetIndex])) {
            return;
        }

        $movedId = $siblingIds[$currentIndex];
        $siblingIds[$currentIndex] = $siblingIds[$targetIndex];
        $siblingIds[$targetIndex] = $movedId;

        DB::transaction(function () use ($siblingIds): void {
            foreach ($siblingIds as $index => $siblingId) {
                TaskCategory::query()
                    ->where('project_id', $this->project->id)
                    ->where('id', $siblingId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        app(TaskTreeService::class)->clearCategoryTreeCache($this->project->id);

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Category order updated.');
    }

    public function copyCategory(): void
    {
        $this->authorize('create', TaskCategory::class);

        $validated = $this->validate([
            'copyCategorySourceId' => [
                'required',
                Rule::exists('task_categories', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
            'copyCategoryDestinationParentId' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '' || $value === '__root__') {
                        return;
                    }

                    $exists = TaskCategory::query()
                        ->where('project_id', $this->project->id)
                        ->whereKey($value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected destination parent is invalid.');
                    }
                },
            ],
            'copyIncludeChildCategories' => ['boolean'],
            'copyIncludeCategoryTasks' => ['boolean'],
            'copyCategoryQuantity' => ['required', 'integer', 'min:1', 'max:50'],
            'copyCategoryNamePrefix' => ['nullable', 'string', 'max:120'],
            'copyCategoryStartNumber' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $copyNamePrefix = trim((string) ($validated['copyCategoryNamePrefix'] ?? ''));
        if ($copyNamePrefix !== '' && ($validated['copyCategoryQuantity'] > 1) && $validated['copyCategoryStartNumber'] === null) {
            $this->addError('copyCategoryStartNumber', 'Starting number is required when creating multiple named copies.');

            return;
        }

        $sourceCategory = TaskCategory::query()->findOrFail($validated['copyCategorySourceId']);
        $destinationParentId = match ($validated['copyCategoryDestinationParentId'] ?? null) {
            '__root__' => null,
            null, '' => $sourceCategory->parent_id,
            default => $validated['copyCategoryDestinationParentId'],
        };

        $copiedCategoryCount = 0;
        $copiedTaskCount = 0;

        DB::transaction(function () use ($sourceCategory, $validated, $destinationParentId, &$copiedCategoryCount, &$copiedTaskCount): void {
            for ($index = 0; $index < $validated['copyCategoryQuantity']; $index++) {
                $rootName = $this->buildCategoryCopyRootName($sourceCategory, $validated, $index);

                $this->copyCategoryRecursive(
                    $sourceCategory,
                    $destinationParentId,
                    $validated['copyIncludeChildCategories'],
                    $validated['copyIncludeCategoryTasks'],
                    $copiedCategoryCount,
                    $copiedTaskCount,
                    $rootName,
                );
            }
        });

        $this->reset('copyCategorySourceId', 'copyCategoryDestinationParentId', 'copyIncludeChildCategories', 'copyIncludeCategoryTasks', 'copyCategoryQuantity', 'copyCategoryNamePrefix', 'copyCategoryStartNumber');
        $this->copyCategoryQuantity = 1;

        app(TaskTreeService::class)->clearCategoryTreeCache($this->project->id);

        $message = "Copied category {$sourceCategory->name}";
        $extraCount = $copiedCategoryCount - 1;
        if ($extraCount > 0) {
            $message .= " with {$extraCount} sub-".($extraCount === 1 ? 'category' : 'categories');
        }
        if ($copiedTaskCount > 0) {
            $message .= " and {$copiedTaskCount} ".($copiedTaskCount === 1 ? 'task' : 'tasks');
        }

        $this->dispatch('close-copy-category-modal');
        $this->dispatchProjectTasksUpdated();
        session()->flash('success', $message.'.');
    }

    public function startInlineCategoryForm(?string $parentCategoryId = null): void
    {
        $this->authorize('create', TaskCategory::class);

        $this->showInlineCategoryForm = true;
        $this->inlineCategoryParentId = $parentCategoryId;
    }

    public function cancelInlineCategoryForm(): void
    {
        $this->reset('inlineCategoryName', 'inlineCategoryDescription', 'inlineCategoryParentId', 'inlineCategoryBatchCount', 'inlineCategoryBatchStartNumber');
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
            'inlineCategoryBatchCount' => ['integer', 'min:1', 'max:100'],
            'inlineCategoryBatchStartNumber' => ['integer', 'min:0', 'max:999999'],
        ]);

        $names = app(TaskBatchTitleGenerator::class)->generate(
            $validated['inlineCategoryName'],
            (int) $validated['inlineCategoryBatchCount'],
            (int) $validated['inlineCategoryBatchStartNumber'],
        );

        DB::transaction(function () use ($names, $validated): void {
            foreach ($names as $name) {
                TaskCategory::query()->create([
                    'project_id' => $this->project->id,
                    'parent_id' => $validated['inlineCategoryParentId'] ?: null,
                    'name' => $name,
                    'description' => $validated['inlineCategoryDescription'] ?: null,
                    'sort_order' => 0,
                    'is_active' => true,
                ]);
            }
        });

        $this->cancelInlineCategoryForm();

        app(TaskTreeService::class)->clearCategoryTreeCache($this->project->id);

        $this->dispatchProjectTasksUpdated();
        session()->flash(
            'success',
            count($names) === 1
                ? 'Category created successfully.'
                : sprintf('%d categories created successfully.', count($names))
        );
    }

    public function startInlineTaskForm(?string $categoryId = null): void
    {
        $this->authorize('create', Task::class);

        $this->showInlineTaskForm = true;
        $this->inlineTaskCategoryId = $categoryId;
    }

    public function cancelInlineTaskForm(): void
    {
        $this->reset('inlineTaskTitle', 'inlineTaskDescription', 'inlineTaskCategoryId', 'inlineTaskAssignedTo', 'inlineTaskBatchCount', 'inlineTaskBatchStartNumber');
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
            'inlineTaskBatchCount' => ['integer', 'min:1', 'max:100'],
            'inlineTaskBatchStartNumber' => ['integer', 'min:0', 'max:999999'],
        ]);

        $titles = app(TaskBatchTitleGenerator::class)->generate(
            $validated['inlineTaskTitle'],
            (int) $validated['inlineTaskBatchCount'],
            (int) $validated['inlineTaskBatchStartNumber'],
        );

        DB::transaction(function () use ($titles, $validated): void {
            foreach ($titles as $title) {
                Task::query()->create([
                    'project_id' => $this->project->id,
                    'task_category_id' => $validated['inlineTaskCategoryId'],
                    'parent_task_id' => null,
                    'title' => $title,
                    'description' => $validated['inlineTaskDescription'] ?: null,
                    'status' => Task::STATUS_TODO,
                    'priority' => Task::PRIORITY_MEDIUM,
                    'completion_percentage' => 0,
                    'assigned_to' => $validated['inlineTaskAssignedTo'] ?: null,
                    'is_billable' => false,
                    'sort_order' => 0,
                ]);
            }
        });

        $this->cancelInlineTaskForm();

        $this->dispatchProjectTasksUpdated();
        session()->flash(
            'success',
            count($titles) === 1
                ? 'Task created successfully.'
                : sprintf('%d tasks created successfully.', count($titles))
        );
    }

    public function startEditTaskTitle(?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

        $task = Task::query()->where('project_id', $this->project->id)->findOrFail($taskId);
        $this->authorize('update', $task);

        $this->editingTaskTitle = $taskId;
        $this->editingTaskTitleValue = $task->title;
    }

    public function cancelEditTaskTitle(): void
    {
        $this->editingTaskTitle = null;
        $this->editingTaskTitleValue = '';
    }

    public function saveTaskTitle(): void
    {
        if ($this->editingTaskTitle === null) {
            return;
        }

        $task = Task::query()->where('project_id', $this->project->id)->findOrFail($this->editingTaskTitle);
        $this->authorize('update', $task);

        $validated = $this->validate([
            'editingTaskTitleValue' => ['required', 'string', 'max:255'],
        ]);

        $task->update(['title' => $validated['editingTaskTitleValue']]);
        $this->cancelEditTaskTitle();

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Task renamed successfully.');
    }

    public function startEditCategoryName(?string $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $category = TaskCategory::query()->where('project_id', $this->project->id)->findOrFail($categoryId);
        $this->authorize('update', $category);

        $this->editingCategoryName = $categoryId;
        $this->editingCategoryNameValue = $category->name;
    }

    public function cancelEditCategoryName(): void
    {
        $this->editingCategoryName = null;
        $this->editingCategoryNameValue = '';
    }

    public function saveCategoryName(): void
    {
        if ($this->editingCategoryName === null) {
            return;
        }

        $category = TaskCategory::query()->where('project_id', $this->project->id)->findOrFail($this->editingCategoryName);
        $this->authorize('update', $category);

        $validated = $this->validate([
            'editingCategoryNameValue' => ['required', 'string', 'max:255'],
        ]);

        $category->update(['name' => $validated['editingCategoryNameValue']]);
        $this->cancelEditCategoryName();

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Category renamed successfully.');
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

    /**
     * @param  int  $copiedCategoryCount  Pass-by-reference counter for categories created.
     * @param  int  $copiedTaskCount  Pass-by-reference counter for tasks created.
     */
    protected function copyCategoryRecursive(
        TaskCategory $sourceCategory,
        ?string $newParentId,
        bool $includeChildCategories,
        bool $includeTasks,
        int &$copiedCategoryCount,
        int &$copiedTaskCount,
        ?string $rootNameOverride = null,
    ): TaskCategory {
        $copyName = $rootNameOverride ?? $sourceCategory->name;

        $newCategory = TaskCategory::query()->create([
            'project_id' => $this->project->id,
            'parent_id' => $newParentId,
            'name' => $copyName,
            'description' => $sourceCategory->description,
            'sort_order' => $sourceCategory->sort_order,
            'is_active' => $sourceCategory->is_active,
        ]);

        $copiedCategoryCount++;

        if ($includeTasks) {
            $tasks = Task::query()
                ->where('project_id', $this->project->id)
                ->where('task_category_id', $sourceCategory->id)
                ->whereNull('parent_task_id')
                ->with('subTasks')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            foreach ($tasks as $task) {
                $taskCopy = Task::query()->create([
                    'project_id' => $this->project->id,
                    'task_category_id' => $newCategory->id,
                    'parent_task_id' => null,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'estimated_hours' => $task->estimated_hours,
                    'completion_percentage' => 0,
                    'due_date' => $task->due_date,
                    'assigned_to' => $task->assigned_to,
                    'is_billable' => $task->is_billable,
                    'sort_order' => $task->sort_order,
                ]);

                $copiedTaskCount++;

                foreach ($task->subTasks as $subTask) {
                    Task::query()->create([
                        'project_id' => $this->project->id,
                        'task_category_id' => $newCategory->id,
                        'parent_task_id' => $taskCopy->id,
                        'title' => $subTask->title,
                        'description' => $subTask->description,
                        'status' => $subTask->status,
                        'priority' => $subTask->priority,
                        'estimated_hours' => $subTask->estimated_hours,
                        'completion_percentage' => 0,
                        'due_date' => $subTask->due_date,
                        'assigned_to' => $subTask->assigned_to,
                        'is_billable' => $subTask->is_billable,
                        'sort_order' => $subTask->sort_order,
                    ]);

                    $copiedTaskCount++;
                }
            }
        }

        if ($includeChildCategories) {
            $children = TaskCategory::query()
                ->where('project_id', $this->project->id)
                ->where('parent_id', $sourceCategory->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($children as $child) {
                $this->copyCategoryRecursive($child, $newCategory->id, true, $includeTasks, $copiedCategoryCount, $copiedTaskCount);
            }
        }

        return $newCategory;
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

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function buildCategoryCopyRootName(TaskCategory $sourceCategory, array $validated, int $index): string
    {
        $quantity = (int) ($validated['copyCategoryQuantity'] ?? 1);
        $namePrefix = trim((string) ($validated['copyCategoryNamePrefix'] ?? ''));
        $startNumber = $validated['copyCategoryStartNumber'] ?? null;

        if ($namePrefix !== '') {
            if ($startNumber === null) {
                return $namePrefix;
            }

            return $namePrefix.' '.((int) $startNumber + $index);
        }

        if ($quantity > 1) {
            return $this->nextCategoryCopyName($sourceCategory->name);
        }

        return $sourceCategory->name;
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

    /**
     * @return array<int, array{title: string, priority: string, estimated_hours: float|int|string|null}>
     */
    protected function buildTemplateTasksForCategoryBranch(TaskCategory $rootCategory): array
    {
        $categoryIds = $this->categoryBranchIds((string) $rootCategory->id);

        /** @var EloquentCollection<int, Task> $parentTasks */
        $parentTasks = Task::query()
            ->where('project_id', $this->project->id)
            ->whereIn('task_category_id', $categoryIds)
            ->whereNull('parent_task_id')
            ->with('subTasks')
            ->orderBy('task_category_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $templateTasks = [];

        foreach ($parentTasks as $task) {
            $templateTasks[] = [
                'title' => $task->title,
                'priority' => $task->priority,
                'estimated_hours' => $task->estimated_hours,
            ];

            foreach ($task->subTasks as $subTask) {
                $templateTasks[] = [
                    'title' => $subTask->title,
                    'priority' => $subTask->priority,
                    'estimated_hours' => $subTask->estimated_hours,
                ];
            }
        }

        return $templateTasks;
    }

    /**
     * @return array<int, string>
     */
    protected function categoryBranchIds(string $rootCategoryId): array
    {
        $ids = [$rootCategoryId];
        $queue = [$rootCategoryId];

        while ($queue !== []) {
            $currentId = array_shift($queue);
            if ($currentId === null) {
                continue;
            }

            $children = TaskCategory::query()
                ->where('project_id', $this->project->id)
                ->where('parent_id', $currentId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('id')
                ->map(fn ($id): string => (string) $id)
                ->all();

            foreach ($children as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    protected function nextTemplateName(string $baseName): string
    {
        $candidate = $baseName;
        $counter = 2;

        while (TaskTemplate::query()->where('name', $candidate)->exists()) {
            $candidate = $baseName.' '.$counter;
            $counter++;
        }

        return $candidate;
    }

    public function startEditTaskStatus(?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

        $task = Task::query()->findOrFail($taskId);
        $this->authorize('updateStatus', $task);

        $this->editingTaskStatus = $taskId;
        $this->editingTaskStatusValue = $task->status;
    }

    public function cancelEditTaskStatus(): void
    {
        $this->editingTaskStatus = null;
        $this->editingTaskStatusValue = null;
    }

    public function saveTaskStatus(): void
    {
        if ($this->editingTaskStatus === null || $this->editingTaskStatusValue === null) {
            return;
        }

        $task = Task::query()->findOrFail($this->editingTaskStatus);
        $this->authorize('updateStatus', $task);

        $validated = $this->validate([
            'editingTaskStatusValue' => ['required', Rule::in(Task::statuses())],
        ]);

        $task->update(['status' => $validated['editingTaskStatusValue']]);
        $this->cancelEditTaskStatus();

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Task status updated successfully.');
    }

    public function startEditTaskPriority(?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

        $task = Task::query()->findOrFail($taskId);
        $this->authorize('updatePriority', $task);

        $this->editingTaskPriority = $taskId;
        $this->editingTaskPriorityValue = $task->priority;
    }

    public function cancelEditTaskPriority(): void
    {
        $this->editingTaskPriority = null;
        $this->editingTaskPriorityValue = null;
    }

    public function saveTaskPriority(): void
    {
        if ($this->editingTaskPriority === null || $this->editingTaskPriorityValue === null) {
            return;
        }

        $task = Task::query()->findOrFail($this->editingTaskPriority);
        $this->authorize('updatePriority', $task);

        $validated = $this->validate([
            'editingTaskPriorityValue' => ['required', Rule::in(Task::priorities())],
        ]);

        $task->update(['priority' => $validated['editingTaskPriorityValue']]);
        $this->cancelEditTaskPriority();

        $this->dispatchProjectTasksUpdated();
        session()->flash('success', 'Task priority updated successfully.');
    }

    protected function dispatchProjectTasksUpdated(): void
    {
        $this->dispatch('project-tasks-updated', projectId: (string) $this->project->id);
    }
}
