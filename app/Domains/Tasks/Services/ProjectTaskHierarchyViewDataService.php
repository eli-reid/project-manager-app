<?php

namespace App\Domains\Tasks\Services;

use App\Core\User\Models\User;
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
        $canViewTaskTemplates = Auth::user()?->hasPermission('task-templates.view') ?? false;

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
            'canViewTaskTemplates' => $canViewTaskTemplates,
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
            $this->appendCategoryOption($options, $category, 0);
        }

        return $options;
    }

    /**
     * @param  array<int, array{id: string, label: string}>  $options
     */
    protected function appendCategoryOption(array &$options, mixed $category, int $depth): void
    {
        $prefix = str_repeat('  ', $depth);

        $options[] = [
            'id' => (string) $category->id,
            'label' => $prefix.$category->name,
        ];

        $children = $category->childrenRecursive ?? collect();
        foreach ($children as $child) {
            $this->appendCategoryOption($options, $child, $depth + 1);
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
     */
    protected function appendCategorySummary(array &$summaries, mixed $category, Collection $tasksByCategory, array $ancestorIds): void
    {
        $categoryId = (string) $category->id;
        /** @var EloquentCollection<int, Task> $categoryTasks */
        $categoryTasks = $tasksByCategory->get($categoryId, collect());

        $taskCount = $categoryTasks->count() + $categoryTasks->sum(fn (Task $task): int => $task->subTasks->count());
        $completedTaskCount = $categoryTasks->where('status', Task::STATUS_COMPLETED)->count()
            + $categoryTasks->sum(fn (Task $task): int => $task->subTasks->where('status', Task::STATUS_COMPLETED)->count());

        $ancestorVisibilityCondition = collect($ancestorIds)
            ->map(fn (string $ancestorId): string => "!isCollapsed('{$ancestorId}')")
            ->implode(' && ');

        $ancestorVisibilityCondition = $ancestorVisibilityCondition !== '' ? $ancestorVisibilityCondition : 'true';

        $summaries[$categoryId] = [
            'taskCount' => $taskCount,
            'completedTaskCount' => $completedTaskCount,
            'progressPercent' => $taskCount > 0 ? (int) round(($completedTaskCount / $taskCount) * 100) : 0,
            'ancestorVisibilityCondition' => $ancestorVisibilityCondition,
            'childrenVisibilityCondition' => $ancestorVisibilityCondition." && !isCollapsed('{$categoryId}')",
        ];

        $children = $category->childrenRecursive ?? collect();
        foreach ($children as $child) {
            $this->appendCategorySummary($summaries, $child, $tasksByCategory, [...$ancestorIds, $categoryId]);
        }
    }
}
