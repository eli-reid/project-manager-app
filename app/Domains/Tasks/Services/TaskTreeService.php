<?php

namespace App\Domains\Tasks\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Tasks\Models\TaskCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TaskTreeService
{
    public function getCachedCategoryTree(?string $projectId = null): Collection
    {
        $projectKey = $projectId ?: 'global';
        $cacheKey = $this->cacheKey($projectKey);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($projectId): Collection {
            return TaskCategory::query()
                ->where('is_active', true)
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
                ->whereNull('parent_id')
                ->with(['childrenRecursive', 'project'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    public function clearCategoryTreeCache(?string $projectId = null): void
    {
        $projectKey = $projectId ?: 'global';
        Cache::forget($this->cacheKey($projectKey));
    }

    private function cacheKey(string $projectKey): string
    {
        return 'tasks.category-tree.'.$projectKey
            .'.cat-'.$this->maxCategoryDepth()
            .'.task-'.$this->maxTaskDepth();
    }

    private function maxCategoryDepth(): int
    {
        return max((int) Settings::get('tasks.max_category_depth', 3)->toInt(), 1);
    }

    private function maxTaskDepth(): int
    {
        return max((int) Settings::get('tasks.max_task_depth', 2)->toInt(), 1);
    }
}
