<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Models\AvailableTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskDefinitionSyncService
{
    public function __construct(
        protected TaskTypeRegistry $registry,
    ) {}

    public function sync(): int
    {
        $definitions = $this->registry->definitions();
        $changes = 0;

        foreach ($definitions as $featureType => $definition) {
            $task = AvailableTask::query()->updateOrCreate([
                'feature_type' => $featureType,
            ], [
                'name' => (string) ($definition['name'] ?? str($featureType)->replace('_', ' ')->headline()->value()),
                'description' => (string) ($definition['description'] ?? ''),
                'is_active' => true,
                'task_config' => is_array($definition['task_config'] ?? null) ? $definition['task_config'] : [],
            ]);

            if ($task->wasRecentlyCreated || $task->wasChanged()) {
                $changes++;
            }
        }

        return $changes;
    }

    public function syncSafely(): int
    {
        try {
            return $this->sync();
        } catch (Throwable $exception) {
            Log::warning('Scheduler task definition sync failed.', [
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }
}
