<?php

namespace App\Core\Scheduler\Livewire\Dashboard;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskStatusService;
use App\Support\Diagnostics\MemoryProbe;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.scheduler.task-health.render.start') : null;
        /** @var Collection<int, ScheduledTask> $tasks */
        $tasks = ScheduledTask::active()
            ->with(['availableTask:id,name'])
            ->orderBy('next_run_at')
            ->get();

        $statusService = app(ScheduledTaskStatusService::class);

        $tasks = $tasks->map(function (ScheduledTask $task) use ($statusService): ScheduledTask {
            $task->runtime_status = $statusService->get($task->id);

            return $task;
        });

        $statusCounts = $tasks->countBy(fn (ScheduledTask $t): string => $t->runtime_status['status'] ?? 'idle');

        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'rendered', [
                'widget' => 'scheduler.task-health',
                'phase' => 'render',
                'tasks_count' => $tasks->count(),
                'tasks_payload' => MemoryProbe::inspect($tasks, 'tasks'),
                'status_counts_payload' => MemoryProbe::inspect($statusCounts, 'status_counts'),
            ]);
        }

        return view('scheduler::livewire.dashboard.widget', [
            'tasks' => $tasks,
            'statusCounts' => $statusCounts,
        ]);
    }
}
