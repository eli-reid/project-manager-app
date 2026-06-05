<?php

namespace App\Domains\Tasks\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectTaskHierarchyViewDataService
{
    /**
     * @return array<string, mixed>
     */
    public function forProject(Project $project): array
    {
        /** @var EloquentCollection<int, Task> $allTasks */
        $allTasks = Task::query()
            ->where('project_id', $project->id)
            ->get(['id', 'status', 'due_date', 'parent_task_id', 'task_category_id']);

        /** @var EloquentCollection<int, Task> $rootTasks */
        $rootTasks = Task::query()
            ->where('project_id', $project->id)
            ->whereNull('parent_task_id')
            ->with([
                'category:id,name,parent_id',
                'assignedTo:id,first_name,last_name',
                'subTasks' => fn ($query) => $query
                    ->with(['assignedTo:id,first_name,last_name'])
                    ->orderBy('sort_order')
                    ->orderBy('title'),
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        /** @var Collection<string, EloquentCollection<int, Task>> $tasksByCategory */
        $tasksByCategory = $rootTasks->groupBy(fn (Task $task) => (string) $task->task_category_id);

        /** @var Collection<int, mixed> $categories */
        $categories = app(TaskTreeService::class)->getCachedCategoryTree($project->id);
        $user = Auth::user();

        $canCreateTask = $user?->hasPermission('tasks.create') ?? false;
        $canUpdateTask = $user?->hasPermission('tasks.edit') ?? false;
        $canDeleteTask = $user?->hasPermission('tasks.delete') ?? false;
        $canUpdateTaskStatus = $canUpdateTask || ($user?->hasPermission('tasks.edit-status') ?? false);
        $canUpdateTaskPriority = $canUpdateTask || ($user?->hasPermission('tasks.edit-priority') ?? false);

        $canCreateTaskCategory = $user?->hasPermission('task-categories.create') ?? false;
        $canUpdateTaskCategory = $user?->hasPermission('task-categories.edit') ?? false;
        $canDeleteTaskCategory = $user?->hasPermission('task-categories.delete') ?? false;

        $canViewTaskTemplates = $user?->hasPermission('task-templates.view') ?? false;
        $canCreateTaskTemplate = $user?->hasPermission('task-templates.create') ?? false;

        return [
            'taskCount' => $allTasks->count(),
            'completedTaskCount' => $allTasks->where('status', Task::STATUS_COMPLETED)->count(),
            'inProgressTaskCount' => $allTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'overdueTaskCount' => $allTasks
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->filter(fn (Task $task) => $task->due_date !== null && $task->due_date->isPast())
                ->count(),
            'categories' => $categories,
            'collapsedCategoryIds' => $this->defaultCollapsedCategoryIds($categories),
            'copyCategoryOptions' => $this->categoryOptions($categories),
            'assignableUsers' => User::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'tasksByCategory' => $tasksByCategory,
            'categorySummaries' => $this->categorySummaries($categories, $tasksByCategory),
            'canCreateTask' => $canCreateTask,
            'canUpdateTask' => $canUpdateTask,
            'canDeleteTask' => $canDeleteTask,
            'canUpdateTaskStatus' => $canUpdateTaskStatus,
            'canUpdateTaskPriority' => $canUpdateTaskPriority,
            'canCreateTaskCategory' => $canCreateTaskCategory,
            'canUpdateTaskCategory' => $canUpdateTaskCategory,
            'canDeleteTaskCategory' => $canDeleteTaskCategory,
            'canViewTaskTemplates' => $canViewTaskTemplates,
            'canCreateTaskTemplate' => $canCreateTaskTemplate,
            'templates' => $canViewTaskTemplates
                ? TaskTemplate::query()
                    ->where('is_active', true)
                    ->with(['category:id,name'])
                    ->latest()
                    ->limit(10)
                    ->get()
                : collect(),
            'hasTaskHierarchy' => $categories->isNotEmpty() || $tasksByCategory->get('', collect())->isNotEmpty(),
            'uncategorizedTasks' => $tasksByCategory->get('', collect()),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $categories
     * @return array<int, string>
     */
    protected function defaultCollapsedCategoryIds(Collection $categories): array
    {
        $collapsed = [];

        foreach ($categories as $category) {
            $collapsed = array_merge($collapsed, $this->descendantCategoryIds($category));
        }

        return array_values(array_unique($collapsed));
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
     * @param  Collection<int, mixed>  $categories
     * @return array<int, array{id: string, label: string}>
     */
    protected function categoryOptions(Collection $categories): array
    {
        $options = [];

        foreach ($categories as $category) {
            $this->appendCategoryOption($options, $category, []);
        }

        return $options;
    }

    /**
     * @param  array<int, array{id: string, label: string}>  $options
     */
    protected function appendCategoryOption(array &$options, mixed $category, array $ancestors): void
    {
        $labelParts = [...$ancestors, $category->name];

        $options[] = [
            'id' => (string) $category->id,
            'label' => implode(' -> ', $labelParts),
        ];

        $children = $category->childrenRecursive ?? collect();
        foreach ($children as $child) {
            $this->appendCategoryOption($options, $child, $labelParts);
        }
    }

    /**
     * @param  Collection<int, mixed>  $categories
     * @param  Collection<string, EloquentCollection<int, Task>>  $tasksByCategory
     * @return array<string, array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}>
     */
    protected function categorySummaries(Collection $categories, Collection $tasksByCategory): array
    {
        $summaries = [];

        foreach ($categories as $category) {
            $this->appendCategorySummary($summaries, $category, $tasksByCategory, []);
        }

        return $summaries;
    }

    /**
     * @param  array<string, array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}>  $summaries
     * @param  Collection<string, EloquentCollection<int, Task>>  $tasksByCategory
     * @param  array<int, string>  $ancestorIds
     * @return array{taskCount: int, completedTaskCount: int}
     */
    protected function appendCategorySummary(array &$summaries, mixed $category, Collection $tasksByCategory, array $ancestorIds): array
    {
        $categoryId = (string) $category->id;
        /** @var EloquentCollection<int, Task> $categoryTasks */
        $categoryTasks = $tasksByCategory->get($categoryId, collect());

        $ownTaskCount = $categoryTasks->count() + $categoryTasks->sum(fn (Task $task): int => $task->subTasks->count());
        $ownCompletedCount = $categoryTasks->where('status', Task::STATUS_COMPLETED)->count()
            + $categoryTasks->sum(fn (Task $task): int => $task->subTasks->where('status', Task::STATUS_COMPLETED)->count());

        $ancestorVisibilityCondition = collect($ancestorIds)
            ->map(fn (string $ancestorId): string => "!isCollapsed('{$ancestorId}')")
            ->implode(' && ');

        $ancestorVisibilityCondition = $ancestorVisibilityCondition !== '' ? $ancestorVisibilityCondition : 'true';

        $descendantTaskCount = 0;
        $descendantCompletedCount = 0;

        $children = $category->childrenRecursive ?? collect();
        foreach ($children as $child) {
            $childTotals = $this->appendCategorySummary($summaries, $child, $tasksByCategory, [...$ancestorIds, $categoryId]);
            $descendantTaskCount += $childTotals['taskCount'];
            $descendantCompletedCount += $childTotals['completedTaskCount'];
        }

        $taskCount = $ownTaskCount + $descendantTaskCount;
        $completedTaskCount = $ownCompletedCount + $descendantCompletedCount;

        $summaries[$categoryId] = [
            'taskCount' => $taskCount,
            'completedTaskCount' => $completedTaskCount,
            'progressPercent' => $taskCount > 0 ? (int) round(($completedTaskCount / $taskCount) * 100) : 0,
            'ancestorVisibilityCondition' => $ancestorVisibilityCondition,
            'childrenVisibilityCondition' => $ancestorVisibilityCondition." && !isCollapsed('{$categoryId}')",
        ];

        return ['taskCount' => $taskCount, 'completedTaskCount' => $completedTaskCount];
    }
}
