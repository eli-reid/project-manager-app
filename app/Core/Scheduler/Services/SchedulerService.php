<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Settings\Facades\Settings;
use Carbon\Carbon;
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
        if (! $this->claimTaskForDispatch($task)) {
            return;
        }

        $task->loadMissing('availableTask');

        Log::info('SchedulerService: queueing task', [
            'task_id' => (string) $task->id,
            'feature_type' => $task->availableTask?->feature_type,
            'schedule_type' => $task->schedule_type,
        ]);

        ProcessScheduledTaskJob::dispatch((string) $task->id)->onQueue('scheduled-tasks');
        app(ScheduledTaskStatusService::class)->markPending((string) $task->id);
    }

    protected function claimTaskForDispatch(ScheduledTask $task): bool
    {
        $nowUtc = now('UTC');
        $claimWindowSeconds = $this->getClaimWindowSeconds();
        $nextDispatchWindow = $nowUtc->copy()->addSeconds($claimWindowSeconds);

        $updatedRows = ScheduledTask::query()
            ->whereKey($task->id)
            ->where('is_active', true)
            ->where('is_enabled', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $nowUtc->format('Y-m-d H:i:s'))
            ->update([
                'next_run_at' => $nextDispatchWindow,
                'updated_at' => Carbon::now(),
            ]);

        return $updatedRows === 1;
    }

    /**
     * Get the claim window in seconds, reading from settings if available, falling back to config/env.
     */
    private function getClaimWindowSeconds(): int
    {
        try {
            $settingValue = Settings::get('scheduler.claim_window_seconds', null);
            if ($settingValue !== null) {
                return (int) $settingValue->raw();
            }
        } catch (\Exception $e) {
            Log::debug('Failed to read scheduler.claim_window_seconds from settings, falling back to config', [
                'error' => $e->getMessage(),
            ]);
        }

        return config('scheduler.claim_window_seconds', 300);
    }
}
