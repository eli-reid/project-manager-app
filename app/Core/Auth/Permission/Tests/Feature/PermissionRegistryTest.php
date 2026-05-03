<?php

use App\Core\Auth\Permission\Services\PermissionRegistry;
use Illuminate\Support\Facades\Log;
use Tests\Feature\PermissionTestContext;

beforeEach(function (): void {
    $this->registry = new PermissionRegistry;
});

it('registers a permission definition', function (): void {
    
    $this->registry->registerPermissions([
        [
            'resource' => 'timecards',
            'action' => 'view',
            'label' => 'View Timecards',
            'description' => 'Allows viewing timecards.',
        ],
    ]);

    expect($this->registry->permissions())->toHaveCount(1)
        ->and($this->registry->permissions()[0]['resource'])->toBe('timecards')
        ->and($this->registry->permissions()[0]['action'])->toBe('view')
        ->and($this->registry->permissions()[0]['label'])->toBe('View Timecards');
});

it('ignores duplicate keys and logs a warning', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'PermissionRegistry')
                && $context['key'] === 'timecards.view'
                && $context['existing_label'] === 'View Timecards';
        });

    $this->registry->registerPermissions([
        ['resource' => 'timecards', 'action' => 'view', 'label' => 'View Timecards'],
    ]);

    $this->registry->registerPermissions([
        ['resource' => 'timecards', 'action' => 'view', 'label' => 'Duplicate Label'],
    ]);

    expect($this->registry->permissions())->toHaveCount(1)
        ->and($this->registry->permissions()[0]['label'])->toBe('View Timecards');
});

it('first registration wins on duplicate key', function (): void {
    Log::spy();

    $this->registry->registerPermissions([
        ['resource' => 'tasks', 'action' => 'create', 'label' => 'Create Tasks — First'],
    ]);

    $this->registry->registerPermissions([
        ['resource' => 'tasks', 'action' => 'create', 'label' => 'Create Tasks — Second'],
    ]);

    expect($this->registry->permissions()[0]['label'])->toBe('Create Tasks — First');
});

it('registers permissions from multiple domains without collision', function (): void {
    $timecardPermissions = [
        ['resource' => 'timecards', 'action' => 'view'],
        ['resource' => 'timecards', 'action' => 'create'],
        ['resource' => 'timecards', 'action' => 'approve'],
    ];

    $taskPermissions = [
        ['resource' => 'tasks', 'action' => 'view'],
        ['resource' => 'tasks', 'action' => 'create'],
    ];

    $projectPermissions = [
        ['resource' => 'projects', 'action' => 'view'],
        ['resource' => 'projects', 'action' => 'edit'],
        ['resource' => 'projects', 'action' => 'delete'],
    ];

    $this->registry->registerPermissions($timecardPermissions);
    $this->registry->registerPermissions($taskPermissions);
    $this->registry->registerPermissions($projectPermissions);

    $keys = collect($this->registry->permissions())
        ->map(fn (array $p): string => $p['resource'].'.'.$p['action'])
        ->all();

    expect($keys)->toContain('timecards.view')
        ->toContain('timecards.create')
        ->toContain('timecards.approve')
        ->toContain('tasks.view')
        ->toContain('tasks.create')
        ->toContain('projects.view')
        ->toContain('projects.edit')
        ->toContain('projects.delete')
        ->and($this->registry->permissions())->toHaveCount(8);
});

it('skips definitions with an empty resource or action', function (): void {
    $this->registry->registerPermissions([
        ['resource' => '', 'action' => 'view'],
        ['resource' => 'tasks', 'action' => ''],
        ['resource' => 'tasks', 'action' => 'create'],
    ]);

    expect($this->registry->permissions())->toHaveCount(1)
        ->and($this->registry->permissions()[0]['resource'])->toBe('tasks')
        ->and($this->registry->permissions()[0]['action'])->toBe('create');
});

it('generates a default label from resource and action when none is provided', function (): void {
    $this->registry->registerPermissions([
        ['resource' => 'daily-reports', 'action' => 'view-all'],
    ]);

    expect($this->registry->permissions()[0]['label'])->toBe('Daily Reports View All');
});

it('grants permissions to built-in roles', function (): void {
    $this->registry->registerPermissions([
        [
            'resource' => 'timecards',
            'action' => 'approve',
            'built_in_roles' => ['admin', 'manager'],
        ],
    ]);

    $rolePermissions = $this->registry->builtInRolePermissions();

    expect($rolePermissions)->toHaveKey('admin')
        ->and($rolePermissions['admin'])->toContain('timecards.approve')
        ->and($rolePermissions['manager'])->toContain('timecards.approve');
});
