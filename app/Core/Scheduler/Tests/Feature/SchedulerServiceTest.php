<?php

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\SchedulerService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

it('calculates next run in utc for active daily tasks', function (): void {
    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Daily test task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:30:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
    ]);

    $nextRun = app(ScheduledTaskService::class)->calculateNextRun($task);

    expect($nextRun)->not->toBeNull()
        ->and($nextRun?->timezoneName)->toBe('UTC')
        ->and($nextRun?->greaterThan(now('UTC')))->toBeTrue();
});

it('queues due scheduled tasks for worker execution', function (): void {
    Queue::fake();

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Due task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => Carbon::now('America/New_York')->format('H:i:s'),
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->subMinute(),
        'run_count' => 0,
    ]);

    app(SchedulerService::class)->run();

    Queue::assertPushed(ProcessScheduledTaskJob::class, function (ProcessScheduledTaskJob $job) use ($task): bool {
        return $job->taskId === (string) $task->id;
    });
});

it('worker job updates run metadata after handling task', function (): void {
    app(TaskTypeRegistry::class)->register('timecard_reminders', NoOpTask::class, [
        'name' => 'Timecard Reminders',
    ]);

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Handled task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => Carbon::now('America/New_York')->format('H:i:s'),
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->subMinute(),
        'run_count' => 0,
    ]);

    $job = new ProcessScheduledTaskJob((string) $task->id);
    $job->handle(app(ScheduledTaskFactory::class));

    $task->refresh();

    expect($task->run_count)->toBe(1)
        ->and($task->last_run_at)->not->toBeNull()
        ->and($task->next_run_at)->not->toBeNull()
        ->and($task->next_run_at?->greaterThan(now('UTC')))->toBeTrue();
});
