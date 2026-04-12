<?php

namespace App\Core\Scheduler\Contracts;

use App\Core\Scheduler\Models\ScheduledTask;


interface SchedulableTask
{
    /**
     * The constructor always receives the ScheduledTask model.
     */
    public function __construct(ScheduledTask $task);

    /**
     * Dispatch the actual job(s) that perform the domain work.
     * This is called by SchedulerService.
     */
    public function dispatchJob(): void;
}

