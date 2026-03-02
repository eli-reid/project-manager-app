<?php

namespace App\Services\Scheduler;

use App\Models\ScheduledTask;
use App\Services\Scheduler\Contracts\SchedulableTask;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    public function __construct(
        protected ScheduledTaskFactory $factory
    ) {}

    /**
     * Entry point: run all due scheduled tasks.
     */
    public function run(): void
    {
        $dueTasks = ScheduledTask::runnable()->get();

        if ($dueTasks->isEmpty()) {
            Log::info('SchedulerService: no runnable tasks found.');
            return;
        }

        Log::info("SchedulerService: processing {$dueTasks->count()} runnable tasks.");

        foreach ($dueTasks as $task) {
            $this->runTaskSafely($task);
        }
    }

    /**
     * Run a single task with logging and isolation.
     */
    protected function runTaskSafely(ScheduledTask $task): void
    {
        Log::info('SchedulerService: running task', [
            'task_id'      => $task->id,
            'feature_type' => $task->feature_type,
            'schedule_type'=> $task->schedule_type,
        ]);

        try {
            // Resolve domain task (e.g. TimecardReminderTask)
            /** @var SchedulableTask $domainTask */
            $domainTask = $this->factory->make($task);

            // Let the domain task dispatch its own queue job(s)
            $domainTask->dispatchJob();

            // Update last_run_at and next_run_at
            $task->markAsRun();

            Log::info('SchedulerService: task completed', [
                'task_id'      => $task->id,
                'next_run_at'  => optional($task->next_run_at)->toIso8601String(),
            ]);

        } catch (\Throwable $e) {
            Log::error('SchedulerService: failed to run task', [
                'task_id'      => $task->id,
                'feature_type' => $task->feature_type,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);

            // You can choose to:
            // - leave next_run_at as-is (retry next cycle)
            // - or push it forward to avoid hammering a broken task
            // For now, we leave it as-is so it will be retried.
        }
    }
}

