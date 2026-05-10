<?php

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Audit\Models\AuditLog;
use App\Core\Identity\Models\User;

use function Pest\Laravel\actingAs;

it('records an audit log with actor target and context', function (): void {
    $actor = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);

    actingAs($actor);

    $auditLog = app(AuditLoggerContract::class)->record('users.edit', $target, [
        'before' => ['is_active' => true],
        'after' => ['is_active' => false],
        'reason' => 'Deactivated by manager',
    ]);

    expect($auditLog->action)->toBe('users.edit')
        ->and($auditLog->actor_type)->toBe($actor->getMorphClass())
        ->and($auditLog->actor_id)->toBe((string) $actor->getKey())
        ->and($auditLog->target_type)->toBe($target->getMorphClass())
        ->and($auditLog->target_id)->toBe((string) $target->getKey())
        ->and($auditLog->before)->toBe(['is_active' => true])
        ->and($auditLog->after)->toBe(['is_active' => false])
        ->and($auditLog->metadata)->toBe(['reason' => 'Deactivated by manager']);

    $this->assertDatabaseHas('audit_logs', [
        'id' => $auditLog->id,
        'action' => 'users.edit',
        'actor_id' => (string) $actor->getKey(),
        'target_id' => (string) $target->getKey(),
    ]);
});

it('records an audit log without actor and with explicit target keys', function (): void {
    $auditLog = app(AuditLoggerContract::class)->record('reports.export', null, [
        'target_type' => 'operational-report',
        'target_id' => 'timecards-weekly',
        'format' => 'csv',
    ]);

    expect($auditLog->actor_type)->toBeNull()
        ->and($auditLog->actor_id)->toBeNull()
        ->and($auditLog->target_type)->toBe('operational-report')
        ->and($auditLog->target_id)->toBe('timecards-weekly')
        ->and($auditLog->metadata)->toBe(['format' => 'csv']);

    expect(AuditLog::query()->count())->toBe(1);
});
