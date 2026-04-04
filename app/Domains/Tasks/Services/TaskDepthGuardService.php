<?php

namespace App\Domains\Tasks\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use Illuminate\Validation\ValidationException;

class TaskDepthGuardService
{
    public function maxCategoryDepth(): int
    {
        return max(Settings::get('tasks.max_category_depth', 3)->toInt(), 1);
    }

    public function maxTaskDepth(): int
    {
        return max(Settings::get('tasks.max_task_depth', 2)->toInt(), 1);
    }

    public function categoryDepthFor(?string $parentCategoryId): int
    {
        if ($parentCategoryId === null || $parentCategoryId === '') {
            return 1;
        }

        return $this->categoryDepthFromCategoryId($parentCategoryId) + 1;
    }

    public function taskDepthFor(?string $parentTaskId): int
    {
        if ($parentTaskId === null || $parentTaskId === '') {
            return 1;
        }

        return $this->taskDepthFromTaskId($parentTaskId) + 1;
    }

    public function assertCategoryDepth(?string $parentCategoryId): void
    {
        $depth = $this->categoryDepthFor($parentCategoryId);

        if ($depth > $this->maxCategoryDepth()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Category depth exceeds configured limit of '.$this->maxCategoryDepth().'.',
            ]);
        }
    }

    public function assertTaskDepth(?string $parentTaskId): void
    {
        $depth = $this->taskDepthFor($parentTaskId);

        if ($depth > $this->maxTaskDepth()) {
            throw ValidationException::withMessages([
                'parent_task_id' => 'Task depth exceeds configured limit of '.$this->maxTaskDepth().'.',
            ]);
        }
    }

    public function assertCombinedDepth(?string $taskCategoryId, ?string $parentTaskId): void
    {
        if ($taskCategoryId === null || $taskCategoryId === '') {
            return;
        }

        $categoryDepth = $this->categoryDepthFromCategoryId($taskCategoryId);
        $taskDepth = $this->taskDepthFor($parentTaskId);
        $maxCombinedDepth = $this->maxCategoryDepth() + $this->maxTaskDepth();

        if (($categoryDepth + $taskDepth) > $maxCombinedDepth) {
            throw ValidationException::withMessages([
                'parent_task_id' => 'Combined category and task chain depth exceeds configured limits.',
            ]);
        }
    }

    private function categoryDepthFromCategoryId(string $categoryId): int
    {
        $depth = 0;
        $currentId = $categoryId;
        $visited = [];

        while ($currentId !== null && $currentId !== '' && ! in_array($currentId, $visited, true)) {
            $visited[] = $currentId;
            $depth++;

            $currentId = TaskCategory::query()->whereKey($currentId)->value('parent_id');
        }

        return max($depth, 1);
    }

    private function taskDepthFromTaskId(string $taskId): int
    {
        $depth = 0;
        $currentId = $taskId;
        $visited = [];

        while ($currentId !== null && $currentId !== '' && ! in_array($currentId, $visited, true)) {
            $visited[] = $currentId;
            $depth++;

            $currentId = Task::query()->whereKey($currentId)->value('parent_task_id');
        }

        return max($depth, 1);
    }
}
