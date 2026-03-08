<?php

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;

it('syncs domain-registered task definitions into scheduled_tasks table', function (): void {
    app(TaskTypeRegistry::class)->register('domain.custom-task', NoOpTask::class, [
        'name' => 'Domain Custom Task',
        'description' => 'Registered from a domain provider.',
        'schedule_type' => 'weekly',
        'time' => '07:00:00',
        'days_of_week' => [1, 3, 5],
        'is_enabled' => false,
        'is_active' => true,
    ]);

    $created = app(TaskDefinitionSyncService::class)->sync();

    expect($created)->toBeGreaterThan(0);

    $task = ScheduledTask::query()->where('feature_type', 'domain.custom-task')->first();

    expect($task)->not->toBeNull()
        ->and($task?->name)->toBe('Domain Custom Task')
        ->and($task?->schedule_type)->toBe('weekly')
        ->and($task?->days_of_week)->toBe([1, 3, 5]);
});
