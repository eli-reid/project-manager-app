<?php

namespace App\Core\Scheduler\Jobs;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessScheduledTaskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 300;

    public function __construct(public string $taskId) {}

    public function handle(ScheduledTaskFactory $factory): void
    {
        $statusService = app(ScheduledTaskStatusService::class);
        $statusService->markRunning($this->taskId, $this->resolveJobUuid());

        $task = ScheduledTask::query()->find($this->taskId);

        if ($task === null) {
            Log::warning('Scheduled task job skipped: task not found.', ['task_id' => $this->taskId]);
            $statusService->markFailed($this->taskId, 'Task not found.', $this->resolveJobUuid());

            return;
        }

        if (! $task->is_active || ! $task->is_enabled) {
            Log::info('Scheduled task job skipped: task inactive or disabled.', ['task_id' => $task->id]);
            $statusService->markFailed($this->taskId, 'Task inactive or disabled.', $this->resolveJobUuid());

            return;
        }

        try {
            $domainTask = $factory->make($task);
            $domainTask->dispatchJob();

            $task->markAsRun();
            $statusService->markCompleted($this->taskId, $this->resolveJobUuid());
        } catch (Throwable $exception) {
            $statusService->markFailed($this->taskId, $exception->getMessage(), $this->resolveJobUuid());

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        app(ScheduledTaskStatusService::class)->markFailed($this->taskId, $exception->getMessage(), $this->resolveJobUuid());
    }

    private function resolveJobUuid(): ?string
    {
        if (! isset($this->job) || $this->job === null) {
            return null;
        }

        if (method_exists($this->job, 'uuid')) {
            $uuid = $this->job->uuid();

            return is_string($uuid) && $uuid !== '' ? $uuid : null;
        }

        if (! method_exists($this->job, 'payload')) {
            return null;
        }

        $payload = $this->job->payload();

        if (! is_array($payload)) {
            return null;
        }

        $uuid = $payload['uuid'] ?? null;

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }
}
