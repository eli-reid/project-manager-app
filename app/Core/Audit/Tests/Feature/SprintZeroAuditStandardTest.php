<?php

use App\Core\Audit\Services\AuditLogger;
use App\Core\Identity\Models\User;

use function Pest\Laravel\actingAs;

it('records audit logs using the sprint zero required before/after/metadata shape', function (): void {
    $actor = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);

    actingAs($actor);

    $entry = app(AuditLogger::class)->record('project-access.grant', $target, [
        'before' => ['permissions' => []],
        'after' => ['permissions' => ['projects.view']],
        'reason' => 'Project assignment update',
    ]);

    expect($entry->action)->toBe('project-access.grant')
        ->and($entry->before)->toBe(['permissions' => []])
        ->and($entry->after)->toBe(['permissions' => ['projects.view']])
        ->and($entry->metadata)->toBe(['reason' => 'Project assignment update']);
});
