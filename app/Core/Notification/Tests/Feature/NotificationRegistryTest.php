<?php

use App\Core\Notification\Services\NotificationRegistry;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->registry = new NotificationRegistry;
});

it('registers a notification definition', function (): void {
    $this->registry->registerDefinitions([
        [
            'key' => 'timecards.submitted',
            'label' => 'Timecard Submitted',
            'description' => 'Fired when a timecard is submitted.',
            'supported_channels' => ['mail', 'database'],
        ],
    ]);

    expect($this->registry->definitions())->toHaveCount(1)
        ->and($this->registry->definitions()[0]['key'])->toBe('timecards.submitted')
        ->and($this->registry->definitions()[0]['label'])->toBe('Timecard Submitted')
        ->and($this->registry->definitions()[0]['supported_channels'])->toBe(['mail', 'database']);
});

it('ignores duplicate keys and logs a warning', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'NotificationRegistry')
                && $context['key'] === 'timecards.submitted'
                && $context['existing_label'] === 'Timecard Submitted';
        });

    $this->registry->registerDefinitions([
        ['key' => 'timecards.submitted', 'label' => 'Timecard Submitted'],
    ]);

    $this->registry->registerDefinitions([
        ['key' => 'timecards.submitted', 'label' => 'Duplicate Label'],
    ]);

    expect($this->registry->definitions())->toHaveCount(1)
        ->and($this->registry->definitions()[0]['label'])->toBe('Timecard Submitted');
});

it('first registration wins on duplicate key', function (): void {
    Log::spy();

    $this->registry->registerDefinitions([
        ['key' => 'tasks.assigned', 'label' => 'Task Assigned — First'],
    ]);

    $this->registry->registerDefinitions([
        ['key' => 'tasks.assigned', 'label' => 'Task Assigned — Second'],
    ]);

    expect($this->registry->definitions()[0]['label'])->toBe('Task Assigned — First');
});

it('registers definitions from multiple domains without collision', function (): void {
    $timecardDefinitions = [
        ['key' => 'timecards.submitted', 'label' => 'Timecard Submitted', 'supported_channels' => ['mail']],
        ['key' => 'timecards.approved', 'label' => 'Timecard Approved', 'supported_channels' => ['mail', 'database']],
    ];

    $taskDefinitions = [
        ['key' => 'tasks.assigned', 'label' => 'Task Assigned', 'supported_channels' => ['database']],
        ['key' => 'tasks.completed', 'label' => 'Task Completed', 'supported_channels' => ['database']],
    ];

    $projectDefinitions = [
        ['key' => 'projects.status-changed', 'label' => 'Project Status Changed', 'supported_channels' => ['mail']],
    ];

    $this->registry->registerDefinitions($timecardDefinitions);
    $this->registry->registerDefinitions($taskDefinitions);
    $this->registry->registerDefinitions($projectDefinitions);

    $keys = collect($this->registry->definitions())->pluck('key')->all();

    expect($keys)->toContain('timecards.submitted')
        ->toContain('timecards.approved')
        ->toContain('tasks.assigned')
        ->toContain('tasks.completed')
        ->toContain('projects.status-changed')
        ->and($this->registry->definitions())->toHaveCount(5);
});

it('skips definitions with an empty key', function (): void {
    $this->registry->registerDefinitions([
        ['key' => '', 'label' => 'No Key'],
        ['key' => 'valid.key', 'label' => 'Valid'],
    ]);

    expect($this->registry->definitions())->toHaveCount(1)
        ->and($this->registry->definitions()[0]['key'])->toBe('valid.key');
});

it('deduplicates supported channels', function (): void {
    $this->registry->registerDefinitions([
        ['key' => 'test.event', 'supported_channels' => ['mail', 'mail', 'database', 'database']],
    ]);

    expect($this->registry->definitions()[0]['supported_channels'])->toBe(['mail', 'database']);
});
