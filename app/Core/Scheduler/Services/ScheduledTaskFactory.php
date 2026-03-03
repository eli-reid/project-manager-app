<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\Contracts\SchedulableTask;

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

