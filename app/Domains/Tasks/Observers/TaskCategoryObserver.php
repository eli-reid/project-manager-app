<?php

namespace App\Domains\Tasks\Observers;

use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskTreeService;

class TaskCategoryObserver
{
    public function __construct(private readonly TaskTreeService $taskTreeService) {}

    public function created(TaskCategory $taskCategory): void
    {
        $this->clearProjectTreeCache((string) $taskCategory->project_id);
    }

    public function updated(TaskCategory $taskCategory): void
    {
        $projectIds = array_filter([
            (string) $taskCategory->project_id,
            (string) $taskCategory->getOriginal('project_id'),
        ]);

        foreach (array_unique($projectIds) as $projectId) {
            $this->clearProjectTreeCache($projectId);
        }
    }

    public function deleted(TaskCategory $taskCategory): void
    {
        $projectId = (string) ($taskCategory->project_id ?? $taskCategory->getOriginal('project_id'));
        $this->clearProjectTreeCache($projectId);
    }

    public function restored(TaskCategory $taskCategory): void
    {
        $this->clearProjectTreeCache((string) $taskCategory->project_id);
    }

    public function forceDeleted(TaskCategory $taskCategory): void
    {
        $projectId = (string) ($taskCategory->project_id ?? $taskCategory->getOriginal('project_id'));
        $this->clearProjectTreeCache($projectId);
    }

    private function clearProjectTreeCache(?string $projectId): void
    {
        if (! filled($projectId)) {
            return;
        }

        $this->taskTreeService->clearCategoryTreeCache($projectId);
    }
}
