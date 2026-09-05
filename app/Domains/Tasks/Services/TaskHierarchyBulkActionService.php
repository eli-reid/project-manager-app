<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskHierarchyBulkActionService
{
    /**
     * @param  array<int, string>  $taskIds
     * @param  array<int, string>  $categoryIds
     * @return array{copiedTasks: int, copiedCategories: int}
     */
    public function copySelected(Project $project, array $taskIds, array $categoryIds): array
    {
        $rootCategories = $this->resolveRootCategories($project, $categoryIds);
        $coveredCategoryIds = $this->coveredCategoryIds($project, $rootCategories);
        $rootTasks = $this->resolveRootCopyTasks($project, $taskIds, $coveredCategoryIds);

        $copiedTasks = 0;
        $copiedCategories = 0;

        DB::transaction(function () use ($project, $rootCategories, $rootTasks, &$copiedTasks, &$copiedCategories): void {
            foreach ($rootCategories as $category) {
                $copiedCategories += $this->copyCategoryBranch($project, $category, $category->parent_id, true, true);
                $copiedTasks += $this->countTasksInCategoryBranch($project, (string) $category->id);
            }

            foreach ($rootTasks as $task) {
                $copiedTasks += $this->copyTaskBranch($project, $task, $task->task_category_id, $task->parent_task_id);
            }
        });

        return [
            'copiedTasks' => $copiedTasks,
            'copiedCategories' => $copiedCategories,
        ];
    }

    /**
     * @param  array<int, string>  $taskIds
     * @param  array<int, string>  $categoryIds
     * @return array{deletedTasks: int, deletedCategories: int, skippedTasks: int}
     */
    public function deleteSelected(Project $project, array $taskIds, array $categoryIds): array
    {
        $rootCategories = $this->resolveRootCategories($project, $categoryIds);
        $coveredCategoryIds = $this->coveredCategoryIds($project, $rootCategories);
        $selectedTasks = $this->resolveDeletionTasks($project, $taskIds, $coveredCategoryIds);

        $deletedTasks = 0;
        $deletedCategories = 0;
        $skippedTasks = 0;

        DB::transaction(function () use ($project, $rootCategories, $selectedTasks, &$deletedTasks, &$deletedCategories, &$skippedTasks): void {
            foreach ($selectedTasks as $task) {
                $hasRemainingChildren = Task::query()
                    ->where('project_id', $project->id)
                    ->where('parent_task_id', $task->id)
                    ->exists();

                if ($hasRemainingChildren) {
                    $skippedTasks++;

                    continue;
                }

                $task->delete();
                $deletedTasks++;
            }

            foreach ($rootCategories as $category) {
                $branchIds = $this->categoryBranchIds($project, (string) $category->id);

                Task::query()
                    ->where('project_id', $project->id)
                    ->whereIn('task_category_id', $branchIds)
                    ->delete();

                TaskCategory::query()
                    ->where('project_id', $project->id)
                    ->whereIn('id', $branchIds)
                    ->delete();

                $deletedCategories += count($branchIds);
            }
        });

        return [
            'deletedTasks' => $deletedTasks,
            'deletedCategories' => $deletedCategories,
            'skippedTasks' => $skippedTasks,
        ];
    }

    /**
     * @param  array<int, string>  $taskIds
     */
    public function markTasksComplete(Project $project, array $taskIds): int
    {
        $normalizedTaskIds = $this->normalizeIds($taskIds);

        if ($normalizedTaskIds === []) {
            return 0;
        }

        return Task::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $normalizedTaskIds)
            ->update([
                'status' => Task::STATUS_COMPLETED,
                'completion_percentage' => 100,
            ]);
    }

    /**
     * @param  array<int, string>  $categoryIds
     * @return Collection<int, TaskCategory>
     */
    public function resolveRootCategories(Project $project, array $categoryIds): Collection
    {
        $normalizedCategoryIds = $this->normalizeIds($categoryIds);

        if ($normalizedCategoryIds === []) {
            return collect();
        }

        $categories = TaskCategory::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $normalizedCategoryIds)
            ->get()
            ->keyBy(fn (TaskCategory $category): string => (string) $category->id);

        return $categories
            ->filter(function (TaskCategory $category) use ($categories): bool {
                $parentId = $category->parent_id;

                while ($parentId !== null) {
                    if ($categories->has((string) $parentId)) {
                        return false;
                    }

                    $parentId = TaskCategory::query()
                        ->whereKey($parentId)
                        ->value('parent_id');
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  Collection<int, TaskCategory>  $rootCategories
     * @return array<int, string>
     */
    public function coveredCategoryIds(Project $project, Collection $rootCategories): array
    {
        $coveredCategoryIds = [];

        foreach ($rootCategories as $category) {
            $coveredCategoryIds = [
                ...$coveredCategoryIds,
                ...$this->categoryBranchIds($project, (string) $category->id),
            ];
        }

        return array_values(array_unique($coveredCategoryIds));
    }

    /**
     * @param  array<int, string>  $taskIds
     * @param  array<int, string>  $coveredCategoryIds
     * @return Collection<int, Task>
     */
    protected function resolveRootCopyTasks(Project $project, array $taskIds, array $coveredCategoryIds): Collection
    {
        $normalizedTaskIds = $this->normalizeIds($taskIds);

        if ($normalizedTaskIds === []) {
            return collect();
        }

        $selectedTaskIds = array_fill_keys($normalizedTaskIds, true);
        $parentCache = [];

        return Task::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $normalizedTaskIds)
            ->get()
            ->filter(function (Task $task) use ($selectedTaskIds, $coveredCategoryIds, &$parentCache): bool {
                if (in_array((string) $task->task_category_id, $coveredCategoryIds, true)) {
                    return false;
                }

                return ! $this->hasSelectedTaskAncestor($task, $selectedTaskIds, $parentCache);
            })
            ->values();
    }

    /**
     * @param  array<int, string>  $taskIds
     * @param  array<int, string>  $coveredCategoryIds
     * @return Collection<int, Task>
     */
    protected function resolveDeletionTasks(Project $project, array $taskIds, array $coveredCategoryIds): Collection
    {
        $normalizedTaskIds = $this->normalizeIds($taskIds);

        if ($normalizedTaskIds === []) {
            return collect();
        }

        return Task::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $normalizedTaskIds)
            ->get()
            ->reject(fn (Task $task): bool => in_array((string) $task->task_category_id, $coveredCategoryIds, true))
            ->sortByDesc(fn (Task $task): int => $this->taskDepthWithinSelection($task, $project))
            ->values();
    }

    protected function taskDepthWithinSelection(Task $task, Project $project): int
    {
        $depth = 0;
        $parentId = $task->parent_task_id;

        while ($parentId !== null) {
            $depth++;
            $parentId = Task::query()
                ->where('project_id', $project->id)
                ->whereKey($parentId)
                ->value('parent_task_id');
        }

        return $depth;
    }

    /**
     * @param  array<string, bool>  $selectedTaskIds
     * @param  array<string, string|null>  $parentCache
     */
    protected function hasSelectedTaskAncestor(Task $task, array $selectedTaskIds, array &$parentCache): bool
    {
        $parentId = $task->parent_task_id;

        while ($parentId !== null) {
            if (isset($selectedTaskIds[(string) $parentId])) {
                return true;
            }

            $parentKey = (string) $parentId;
            if (! array_key_exists($parentKey, $parentCache)) {
                $parentCache[$parentKey] = Task::query()->whereKey($parentId)->value('parent_task_id');
            }

            $parentId = $parentCache[$parentKey];
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function categoryBranchIds(Project $project, string $rootCategoryId): array
    {
        $ids = [$rootCategoryId];
        $queue = [$rootCategoryId];

        while ($queue !== []) {
            $currentId = array_shift($queue);
            if ($currentId === null) {
                continue;
            }

            $children = TaskCategory::query()
                ->where('project_id', $project->id)
                ->where('parent_id', $currentId)
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

    protected function copyCategoryBranch(Project $project, TaskCategory $sourceCategory, ?string $newParentId, bool $includeChildCategories, bool $includeTasks, ?string $rootNameOverride = null): int
    {
        $copyName = $rootNameOverride ?? $this->nextCategoryCopyName($project, $sourceCategory->name);

        $newCategory = TaskCategory::query()->create([
            'project_id' => $project->id,
            'parent_id' => $newParentId,
            'name' => $copyName,
            'description' => $sourceCategory->description,
            'sort_order' => $sourceCategory->sort_order,
            'is_active' => $sourceCategory->is_active,
        ]);

        $copiedCategories = 1;

        if ($includeTasks) {
            $tasks = Task::query()
                ->where('project_id', $project->id)
                ->where('task_category_id', $sourceCategory->id)
                ->whereNull('parent_task_id')
                ->with('subTasks')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            foreach ($tasks as $task) {
                $this->copyTaskBranch($project, $task, $newCategory->id, null);
            }
        }

        if ($includeChildCategories) {
            $children = TaskCategory::query()
                ->where('project_id', $project->id)
                ->where('parent_id', $sourceCategory->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($children as $child) {
                $copiedCategories += $this->copyCategoryBranch($project, $child, $newCategory->id, true, $includeTasks, $child->name);
            }
        }

        return $copiedCategories;
    }

    protected function copyTaskBranch(Project $project, Task $sourceTask, ?string $categoryId, ?string $parentTaskId): int
    {
        $taskCopy = Task::query()->create([
            'project_id' => $project->id,
            'task_category_id' => $categoryId,
            'parent_task_id' => $parentTaskId,
            'title' => $this->nextTaskCopyTitle($project, $sourceTask->title),
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

        $copiedTasks = 1;

        $subTasks = $sourceTask->relationLoaded('subTasks')
            ? $sourceTask->subTasks
            : $sourceTask->subTasks()->orderBy('sort_order')->orderBy('title')->get();

        foreach ($subTasks as $subTask) {
            $copiedTasks += $this->copyTaskBranch($project, $subTask, $categoryId, $taskCopy->id);
        }

        return $copiedTasks;
    }

    protected function nextCategoryCopyName(Project $project, string $sourceName): string
    {
        $baseName = $sourceName.' (Copy)';
        $candidate = $baseName;
        $counter = 2;

        while (TaskCategory::query()
            ->where('project_id', $project->id)
            ->where('name', $candidate)
            ->exists()) {
            $candidate = $baseName.' '.$counter;
            $counter++;
        }

        return $candidate;
    }

    protected function nextTaskCopyTitle(Project $project, string $sourceTitle): string
    {
        $baseTitle = $sourceTitle.' (Copy)';
        $candidate = $baseTitle;
        $counter = 2;

        while (Task::query()
            ->where('project_id', $project->id)
            ->where('title', $candidate)
            ->exists()) {
            $candidate = $baseTitle.' '.$counter;
            $counter++;
        }

        return $candidate;
    }

    protected function countTasksInCategoryBranch(Project $project, string $rootCategoryId): int
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->whereIn('task_category_id', $this->categoryBranchIds($project, $rootCategoryId))
            ->count();
    }
}
