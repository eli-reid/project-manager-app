<?php

namespace App\Domains\Timecards\Tasks;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\Contracts\SchedulableTask;
use App\Domains\Timecards\Services\TimecardReminderService;
use Illuminate\Support\Facades\Log;

class TimecardReminderTask implements SchedulableTask
{
    public function __construct(protected ScheduledTask $task,) 
    {
        
    }

    public function dispatchJob(): void
    {
        $sentCount = app(TimecardReminderService::class)->sendPendingReminderNotifications(
            is_array($this->task->task_config) ? $this->task->task_config : []
        );

        Log::info('Timecard reminder task completed.', [
            'task_id' => (string) $this->task->id,
            'feature_type' => $this->task->availableTask?->feature_type,
            'sent_count' => $sentCount,
        ]);
    }
}
