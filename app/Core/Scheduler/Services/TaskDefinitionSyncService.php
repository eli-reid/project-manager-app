<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Models\ScheduledTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskDefinitionSyncService
{
    public function __construct(
        protected TaskTypeRegistry $registry,
        protected ScheduledTaskService $scheduledTaskService,
    ) {}

    public function sync(): int
    {
        $definitions = $this->registry->definitions();
        $createdCount = 0;

        foreach ($definitions as $featureType => $definition) {
            $existing = ScheduledTask::query()
                ->where('feature_type', $featureType)
                ->exists();

            if ($existing) {
                continue;
            }

            $task = ScheduledTask::query()->create([
                'name' => (string) ($definition['name'] ?? str($featureType)->replace('_', ' ')->headline()->value()),
                'feature_type' => $featureType,
                'description' => (string) ($definition['description'] ?? ''),
                'schedule_type' => (string) ($definition['schedule_type'] ?? 'daily'),
                'time' => (string) ($definition['time'] ?? '09:00:00'),
                'timezone' => (string) ($definition['timezone'] ?? 'America/New_York'),
                'days_of_week' => $definition['days_of_week'] ?? null,
                'day_of_month' => $definition['day_of_month'] ?? null,
                'month' => $definition['month'] ?? null,
                'specific_date' => $definition['specific_date'] ?? null,
                'repeat_frequency' => (string) ($definition['repeat_frequency'] ?? 'once'),
                'repeat_interval' => (int) ($definition['repeat_interval'] ?? 1),
                'repeat_until' => $definition['repeat_until'] ?? null,
                'max_occurrences' => $definition['max_occurrences'] ?? null,
                'is_active' => (bool) ($definition['is_active'] ?? true),
                'is_enabled' => (bool) ($definition['is_enabled'] ?? false),
                'task_config' => is_array($definition['task_config'] ?? null) ? $definition['task_config'] : [],
                'created_by' => null,
                'updated_by' => null,
            ]);

            $task->update([
                'next_run_at' => $this->scheduledTaskService->calculateNextRun($task),
            ]);

            $createdCount++;
        }

        return $createdCount;
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
