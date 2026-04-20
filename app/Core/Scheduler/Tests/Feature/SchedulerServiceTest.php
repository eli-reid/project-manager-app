<?php

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskFactory;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Core\Scheduler\Services\ScheduledTaskStatusService;
use App\Core\Scheduler\Services\SchedulerService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;
use Carbon\Carbon;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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

it('does not queue the same due task twice before worker processes it', function (): void {
    Queue::fake();

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Due once task',
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
    app(SchedulerService::class)->run();

    Queue::assertPushedTimes(ProcessScheduledTaskJob::class, 1);
    Queue::assertPushed(ProcessScheduledTaskJob::class, function (ProcessScheduledTaskJob $job) use ($task): bool {
        return $job->taskId === (string) $task->id;
    });

    $task->refresh();

    expect($task->next_run_at)->not->toBeNull()
        ->and($task->next_run_at?->greaterThan(now('UTC')))->toBeTrue();
});

it('marks task status as pending when scheduler dispatches a due task', function (): void {
    Queue::fake();

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Pending status task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => Carbon::now('America/New_York')->format('H:i:s'),
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->subMinute(),
    ]);

    app(SchedulerService::class)->run();

    $status = app(ScheduledTaskStatusService::class)->get((string) $task->id);

    expect($status['status'])->toBe('pending');
});

it('prevents overlapping execution of the same scheduled task job', function (): void {
    $job = new ProcessScheduledTaskJob('task-123');

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
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
    $status = app(ScheduledTaskStatusService::class)->get((string) $task->id);

    expect($task->run_count)->toBe(1)
        ->and($task->last_run_at)->not->toBeNull()
        ->and($task->next_run_at)->not->toBeNull()
        ->and($task->next_run_at?->greaterThan(now('UTC')))->toBeTrue()
        ->and($status['status'])->toBe('completed');
});

it('marks task status as failed when worker job throws', function (): void {
    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'unknown_task_type_for_status_test',
        'name' => 'Unknown Task Type',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Failed status task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => Carbon::now('America/New_York')->format('H:i:s'),
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->subMinute(),
    ]);

    $job = new ProcessScheduledTaskJob((string) $task->id);

    expect(fn () => $job->handle(app(ScheduledTaskFactory::class)))->toThrow(RuntimeException::class);

    $status = app(ScheduledTaskStatusService::class)->get((string) $task->id);

    expect($status['status'])->toBe('failed')
        ->and($status['error'])->toContain('Unknown task type');
});

it('finds due tasks using utc comparison even when app timezone differs', function (): void {
    $originalTimezone = config('app.timezone');
    config()->set('app.timezone', 'America/New_York');
    Carbon::setTestNow(Carbon::parse('2026-04-18 15:00:00', 'UTC'));

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $dueTask = ScheduledTask::query()->create([
        'name' => 'Due in UTC',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->subMinutes(30),
    ]);

    $futureTask = ScheduledTask::query()->create([
        'name' => 'Future in UTC',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now('UTC')->addMinutes(30),
    ]);

    $dueIds = ScheduledTask::due()->pluck('id')->all();

    expect($dueIds)
        ->toContain($dueTask->id)
        ->and($dueIds)->not->toContain($futureTask->id);

    Carbon::setTestNow();
    config()->set('app.timezone', $originalTimezone);
});
