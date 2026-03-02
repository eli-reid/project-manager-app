<?php

namespace App\Services\Scheduler;

use App\Models\ScheduledTask;
use App\Services\Scheduler\Contracts\SchedulableTask;
use App\Domain\Timecard\Tasks\TimecardReminderTask;

class ScheduledTaskFactory
{
    public function make(ScheduledTask $task): SchedulableTask
    {
        return match ($task->feature_type) {
            'timecard.reminder' => app()->make(TimecardReminderTask::class, [
                'task' => $task,
            ]),

            default => throw new \RuntimeException(
                "Unknown scheduled task feature_type: {$task->feature_type}"
            ),
        };
    }
}

