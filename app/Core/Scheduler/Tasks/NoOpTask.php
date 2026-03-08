<?php

namespace App\Core\Scheduler\Tasks;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\Contracts\SchedulableTask;
use Illuminate\Support\Facades\Log;

class NoOpTask implements SchedulableTask
{
    public function __construct(protected ScheduledTask $task) {}

    public function dispatchJob(): void
    {
        Log::info('NoOp scheduler task executed.', [
            'task_id' => $this->task->id,
            'feature_type' => $this->task->feature_type,
            'name' => $this->task->name,
        ]);
    }
}
