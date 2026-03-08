<?php

use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;

it('syncs domain-registered task definitions into available_tasks table', function (): void {
    app(TaskTypeRegistry::class)->register('domain.custom-task', NoOpTask::class, [
        'name' => 'Domain Custom Task',
        'description' => 'Registered from a domain provider.',
        'task_config' => [
            'source' => 'domain-provider',
        ],
        // These should be ignored by registry/sync.
        'schedule_type' => 'weekly',
        'time' => '07:00:00',
        'days_of_week' => [1, 3, 5],
    ]);

    $created = app(TaskDefinitionSyncService::class)->sync();

    expect($created)->toBeGreaterThan(0);

    $task = AvailableTask::query()->where('feature_type', 'domain.custom-task')->first();

    expect($task)->not->toBeNull()
        ->and($task?->name)->toBe('Domain Custom Task')
        ->and($task?->is_active)->toBeTrue()
        ->and($task?->task_config)->toMatchArray([
            'source' => 'domain-provider',
        ]);
});
