<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Models\ScheduledTask;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    /**
     * Entry point: run all due scheduled tasks.
     */
    public function run(): void
    {
        $dueTasks = ScheduledTask::due()->get();

        if ($dueTasks->isEmpty()) {
            Log::info('SchedulerService: no runnable tasks found.');

            return;
        }

        Log::info("SchedulerService: processing {$dueTasks->count()} runnable tasks.");

        foreach ($dueTasks as $task) {
            $this->queueTask($task);
        }
    }

    /**
     * Queue a single task to be processed by Laravel workers.
     */
    protected function queueTask(ScheduledTask $task): void
    {
        $task->loadMissing('availableTask');

        Log::info('SchedulerService: queueing task', [
            'task_id' => (string) $task->id,
            'feature_type' => $task->availableTask?->feature_type,
            'schedule_type' => $task->schedule_type,
        ]);

        ProcessScheduledTaskJob::dispatch((string) $task->id)->onQueue('scheduled-tasks');
    }
}
