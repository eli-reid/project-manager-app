<?php

namespace App\Core\Scheduler\Services;

use App\Core\Scheduler\Contracts\SchedulableTask;
use App\Core\Scheduler\Models\ScheduledTask;

class ScheduledTaskFactory
{
    public function __construct(
        protected TaskTypeRegistry $registry
    ) {}

    public function make(ScheduledTask $task): SchedulableTask
    {
        $task->loadMissing('availableTask');

        $featureType = $task->availableTask?->feature_type;

        if ($featureType === null || $featureType === '') {
            throw new \RuntimeException("Scheduled task {$task->id} is missing an available task definition.");
        }

        $class = $this->registry->resolve($featureType);

        if (! $class) {
            throw new \RuntimeException("Unknown task type: {$featureType}");
        }

        return app()->make($class, ['task' => $task]);
    }
}
