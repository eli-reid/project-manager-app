<?php

namespace App\Services\Scheduler;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Services\Scheduler\Contracts\SchedulableTask;

class ScheduledTaskFactory
{
    public function __construct(
        protected TaskTypeRegistry $registry
    ) {}

    public function make(ScheduledTask $task): SchedulableTask
    {
        $class = $this->registry->resolve($task->feature_type);

        if (!$class) {
            throw new \RuntimeException("Unknown task type: {$task->feature_type}");
        }

        return app()->make($class, ['task' => $task]);
    }
}

