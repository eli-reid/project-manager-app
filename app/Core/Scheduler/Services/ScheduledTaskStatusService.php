<?php

namespace App\Core\Scheduler\Services;

use Illuminate\Support\Facades\Cache;

class ScheduledTaskStatusService
{
    private const CACHE_TTL_DAYS = 2;

    public function markPending(string $taskId): void
    {
        $this->store($taskId, [
            'status' => 'pending',
        ]);
    }

    public function markRunning(string $taskId, ?string $jobUuid = null): void
    {
        $this->store($taskId, [
            'status' => 'running',
            'job_uuid' => $jobUuid,
            'started_at' => now('UTC')->toISOString(),
        ]);
    }

    public function markCompleted(string $taskId, ?string $jobUuid = null): void
    {
        $this->store($taskId, [
            'status' => 'completed',
            'job_uuid' => $jobUuid,
            'finished_at' => now('UTC')->toISOString(),
            'error' => null,
        ]);
    }

    public function markFailed(string $taskId, ?string $error = null, ?string $jobUuid = null): void
    {
        $this->store($taskId, [
            'status' => 'failed',
            'job_uuid' => $jobUuid,
            'finished_at' => now('UTC')->toISOString(),
            'error' => $error,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $taskId): array
    {
        return Cache::get($this->cacheKey($taskId), [
            'task_id' => $taskId,
            'status' => 'idle',
            'updated_at' => now('UTC')->toISOString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(string $taskId, array $attributes): void
    {
        $existing = Cache::get($this->cacheKey($taskId), [
            'task_id' => $taskId,
        ]);

        Cache::put(
            $this->cacheKey($taskId),
            array_merge($existing, $attributes, [
                'task_id' => $taskId,
                'updated_at' => now('UTC')->toISOString(),
            ]),
            now()->addDays(self::CACHE_TTL_DAYS),
        );
    }

    private function cacheKey(string $taskId): string
    {
        return "scheduler.task.status.{$taskId}";
    }
}
