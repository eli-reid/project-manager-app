<?php

namespace App\Core\Scheduler\Jobs;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $task = ScheduledTask::query()->find($this->taskId);

        if ($task === null) {
            Log::warning('Scheduled task job skipped: task not found.', ['task_id' => $this->taskId]);

            return;
        }

        if (! $task->is_active || ! $task->is_enabled) {
            Log::info('Scheduled task job skipped: task inactive or disabled.', ['task_id' => $task->id]);

            return;
        }

        $domainTask = $factory->make($task);
        $domainTask->dispatchJob();

        $task->markAsRun();
    }
}
