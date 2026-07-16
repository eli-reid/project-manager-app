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
    public function forProject(Project $project, array $expandedCategoryIds = [], ?Collection $categories = null): array
    {
        $expandedCategoryLookup = array_fill_keys($expandedCategoryIds, true);

        $taskMetrics = Task::query()
            ->where('project_id', $project->id)
            ->selectRaw('COUNT(*) as task_count')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_task_count', [Task::STATUS_COMPLETED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_task_count', [Task::STATUS_IN_PROGRESS])
            ->selectRaw(
                'SUM(CASE WHEN status != ? AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_task_count',
                [Task::STATUS_COMPLETED, now()]
            )
            ->first();

        /** @var EloquentCollection<int, Task> $rootTasks */
        $rootTasks = Task::query()
            ->where('project_id', $project->id)
            ->whereNull('parent_task_id')
            ->with([
                'assignedTo:id,first_name,last_name',
                'subTasks' => fn ($query) => $query
                    ->with(['assignedTo:id,first_name,last_name'])
                    ->orderBy('title'),
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        /** @var Collection<string, EloquentCollection<int, Task>> $tasksByCategory */
        $tasksByCategory = $rootTasks->groupBy(fn (Task $task) => (string) $task->task_category_id);

        /** @var Collection<int, mixed> $categories */
        $categories ??= app(TaskTreeService::class)->getCachedCategoryTree($project->id);
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

        $taskCount = (int) ($taskMetrics?->task_count ?? 0);
        $completedTaskCount = (int) ($taskMetrics?->completed_task_count ?? 0);
        $inProgressTaskCount = (int) ($taskMetrics?->in_progress_task_count ?? 0);
        $overdueTaskCount = (int) ($taskMetrics?->overdue_task_count ?? 0);
        $templates = $canViewTaskTemplates
            ? TaskTemplate::query()
                ->where('is_active', true)
                ->with(['category:id,name'])
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $categorySummaries = $this->categorySummaries($categories, $tasksByCategory);
        $flatCategories = $this->flatCategories($categories, $tasksByCategory, $categorySummaries, $expandedCategoryLookup);
        $uncategorizedTaskRows = $this->taskRows(
            $tasksByCategory->get('', collect()),
            0,
            null,
            'uncategorized-task-row',
            'Task',
            '',
            true,
            true,
            false,
        );

        return [
            'taskCount' => $taskCount,
            'completedTaskCount' => $completedTaskCount,
            'inProgressTaskCount' => $inProgressTaskCount,
            'overdueTaskCount' => $overdueTaskCount,
            'metricCards' => [
                [
                    'label' => 'Total',
                    'value' => (string) $taskCount,
                    'valueClass' => 'text-zinc-900 dark:text-zinc-100',
                ],
                [
                    'label' => 'In Progress',
                    'value' => (string) $inProgressTaskCount,
                    'valueClass' => 'text-amber-600 dark:text-amber-400',
                ],
                [
                    'label' => 'Completed',
                    'value' => (string) $completedTaskCount,
                    'valueClass' => 'text-emerald-600 dark:text-emerald-400',
                ],
                [
                    'label' => 'Overdue',
                    'value' => (string) $overdueTaskCount,
                    'valueClass' => 'text-rose-600 dark:text-rose-400',
                ],
            ],
            'categories' => $categories,
            'flatCategories' => $flatCategories,
            'copyCategoryOptions' => $this->categoryOptions($categories),
            'assignableUsers' => User::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'tasksByCategory' => $tasksByCategory,
            'categorySummaries' => $categorySummaries,
            'flatRows' => $this->flatRows($flatCategories, $uncategorizedTaskRows),
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
            'templates' => $templates,
            'templateItems' => $templates
                ->map(fn (TaskTemplate $template): array => [
                    'id' => (string) $template->id,
                    'name' => $template->name,
                    'priorityLabel' => ucfirst($template->priority),
                ])
                ->values()
                ->all(),
            'taskTemplateManageUrl' => route('admin.task-templates.index'),
            'hasTaskHierarchy' => $categories->isNotEmpty() || $tasksByCategory->get('', collect())->isNotEmpty(),
            'uncategorizedTasks' => $tasksByCategory->get('', collect()),
            'uncategorizedTaskRows' => $uncategorizedTaskRows,
        ];
    }

    /**
     * @param  array<int, array{category: mixed, depth: int, categoryId: string, summary: array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}, categoryIndent: int, progressWidth: string, taskRows: array<int, array<string, mixed>>}>  $flatCategories
     * @param  array<int, array<string, mixed>>  $uncategorizedTaskRows
     * @return array<int, array{type: 'category'|'task', categoryRow?: array<string, mixed>, taskRow?: array<string, mixed>}>
     */
    protected function flatRows(array $flatCategories, array $uncategorizedTaskRows): array
    {
        $rows = [];

        foreach ($flatCategories as $categoryRow) {
            $rows[] = [
                'type' => 'category',
                'categoryRow' => $categoryRow,
            ];

            foreach ($this->flattenTaskRows($categoryRow['taskRows']) as $taskRow) {
                $rows[] = [
                    'type' => 'task',
                    'taskRow' => $taskRow,
                ];
            }
        }

        foreach ($this->flattenTaskRows($uncategorizedTaskRows) as $taskRow) {
            $rows[] = [
                'type' => 'task',
                'taskRow' => $taskRow,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $taskRows
     * @return array<int, array<string, mixed>>
     */
    protected function flattenTaskRows(array $taskRows): array
    {
        $rows = [];

        foreach ($taskRows as $taskRow) {
            $rows[] = $taskRow;

            foreach ($this->flattenTaskRows($taskRow['subTaskRows'] ?? []) as $subTaskRow) {
                $rows[] = $subTaskRow;
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, mixed>  $categories
     * @param  Collection<string, EloquentCollection<int, Task>>  $tasksByCategory
     * @param  array<string, array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}>  $categorySummaries
     * @param  array<string, bool>  $expandedCategoryLookup
     * @return array<int, array{category: mixed, depth: int, categoryId: string, summary: array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}, categoryIndent: int, progressWidth: string, taskRows: array<int, array<string, mixed>>}>
     */
    protected function flatCategories(Collection $categories, Collection $tasksByCategory, array $categorySummaries, array $expandedCategoryLookup): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $this->appendFlatCategoryRow($rows, $category, 0, $tasksByCategory, $categorySummaries, $expandedCategoryLookup);
        }

        return $rows;
    }

    /**
     * @param  array<int, array{category: mixed, depth: int, categoryId: string, summary: array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}, categoryIndent: int, progressWidth: string, taskRows: array<int, array<string, mixed>>}>  $rows
     * @param  Collection<string, EloquentCollection<int, Task>>  $tasksByCategory
     * @param  array<string, array{taskCount: int, completedTaskCount: int, progressPercent: int, ancestorVisibilityCondition: string, childrenVisibilityCondition: string}>  $categorySummaries
     * @param  array<string, bool>  $expandedCategoryLookup
     */
    protected function appendFlatCategoryRow(array &$rows, mixed $category, int $depth, Collection $tasksByCategory, array $categorySummaries, array $expandedCategoryLookup): void
    {
        $categoryId = (string) $category->id;
        $summary = $categorySummaries[$categoryId] ?? [
            'taskCount' => 0,
            'completedTaskCount' => 0,
            'progressPercent' => 0,
            'ancestorVisibilityCondition' => 'true',
            'childrenVisibilityCondition' => "!isCollapsed('{$categoryId}')",
        ];
        $taskIndent = (($depth + 1) * 18) + 20;
        $subTaskIndent = (($depth + 2) * 18) + 28;
        $isExpanded = $expandedCategoryLookup[$categoryId] ?? false;

        $rows[] = [
            'category' => $category,
            'depth' => $depth,
            'categoryId' => $categoryId,
            'summary' => $summary,
            'categoryIndent' => ($depth * 18) + 12,
            'progressWidth' => $summary['progressPercent'].'%',
            'taskRows' => $isExpanded
                ? $this->taskRows(
                    $tasksByCategory->get($categoryId, collect()),
                    $taskIndent,
                    $subTaskIndent,
                )
                : [],
        ];

        if (! $isExpanded) {
            return;
        }

        $children = $category->childrenRecursive ?? collect();
        foreach ($children as $child) {
            $this->appendFlatCategoryRow($rows, $child, $depth + 1, $tasksByCategory, $categorySummaries, $expandedCategoryLookup);
        }
    }

    /**
     * @param  Collection<int, Task>|EloquentCollection<int, Task>  $tasks
     * @return array<int, array<string, mixed>>
     */
    protected function taskRows(
        Collection|EloquentCollection $tasks,
        ?int $indent,
        ?int $subTaskIndent,
        string $keyPrefix = 'task-row',
        string $typeLabel = 'Task',
        string $titlePrefix = '',
        bool $supportsInlineStatusEditing = true,
        bool $supportsInlinePriorityEditing = true,
        bool $includeSubTasks = true,
    ): array {
        return $tasks
            ->map(function (Task $task) use ($indent, $subTaskIndent, $keyPrefix, $typeLabel, $titlePrefix, $supportsInlineStatusEditing, $supportsInlinePriorityEditing, $includeSubTasks): array {
                return [
                    'task' => $task,
                    'taskId' => (string) $task->id,
                    'indent' => $indent,
                    'keyPrefix' => $keyPrefix,
                    'typeLabel' => $typeLabel,
                    'titlePrefix' => $titlePrefix,
                    'displayTitle' => $titlePrefix.$task->title,
                    'statusLabel' => $this->taskStatusLabel($task),
                    'priorityLabel' => $this->taskPriorityLabel($task),
                    'assignedLabel' => $this->assignedName($task),
                    'assignedName' => $this->assignedName($task),
                    'supportsInlineStatusEditing' => $supportsInlineStatusEditing,
                    'supportsInlinePriorityEditing' => $supportsInlinePriorityEditing,
                    'subTaskRows' => $includeSubTasks && $subTaskIndent !== null
                        ? $this->taskRows(
                            $task->subTasks,
                            $subTaskIndent,
                            null,
                            'subtask-row',
                            'Subtask',
                            '-> ',
                            false,
                            false,
                            false,
                        )
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    protected function taskStatusLabel(Task $task): string
    {
        return str($task->status)->replace('_', ' ')->headline()->value();
    }

    protected function taskPriorityLabel(Task $task): string
    {
        return ucfirst($task->priority);
    }

    protected function assignedName(Task $task): string
    {
        if (! $task->assignedTo) {
            return '—';
        }

        $fullName = trim($task->assignedTo->first_name.' '.$task->assignedTo->last_name);

        return $fullName !== '' ? $fullName : '—';
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
