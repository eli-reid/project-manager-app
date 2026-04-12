<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Identity\Models\User;
use App\Domains\Payroll\Enums\PayRunStatus;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Permissions\PayrollPermissions;

// ─── PayrollPermissions coverage ─────────────────────────────────────────────

it('registers all permission areas after sync', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $resources = collect(PayrollPermissions::all())->pluck('resource')->unique()->values();

    foreach ($resources as $resource) {
        expect(
            Permission::query()->where('resource', $resource)->exists()
        )->toBeTrue("Missing permissions for resource: {$resource}");
    }
});

it('registers the full expected permission set', function (): void {
    $keys = collect(PayrollPermissions::all())
        ->map(fn (array $p) => "{$p['resource']}.{$p['action']}")
        ->all();

    expect($keys)->toContain('payroll-employees.view')
        ->toContain('payroll-employees.create')
        ->toContain('payroll-employees.update')
        ->toContain('payroll-employees.deactivate')
        ->toContain('payroll-timecards.view')
        ->toContain('payroll-timecards.submit')
        ->toContain('payroll-timecards.approve')
        ->toContain('payroll-timecards.bulk-enter')
        ->toContain('payroll-runs.preview')
        ->toContain('payroll-runs.approve')
        ->toContain('payroll-runs.finalize')
        ->toContain('payroll-runs.void')
        ->toContain('payroll-stubs.view-own')
        ->toContain('payroll-stubs.view-all')
        ->toContain('payroll-deductions.configure')
        ->toContain('payroll-deductions.assign')
        ->toContain('payroll-rates.view')
        ->toContain('payroll-rates.manage')
        ->toContain('payroll-reports.view')
        ->toContain('payroll-reports.certify');
});

// ─── PayRunStatus enum ────────────────────────────────────────────────────────

it('casts pay run status to PayRunStatus enum', function (): void {
    $run = PayRun::factory()->create(['status' => PayRunStatus::Draft]);

    expect($run->fresh()->status)->toBeInstanceOf(PayRunStatus::class)
        ->and($run->fresh()->status)->toBe(PayRunStatus::Draft);
});

it('marks finalized and void statuses as locked', function (): void {
    expect(PayRunStatus::Finalized->isLocked())->toBeTrue()
        ->and(PayRunStatus::Void->isLocked())->toBeTrue()
        ->and(PayRunStatus::Draft->isLocked())->toBeFalse()
        ->and(PayRunStatus::Preview->isLocked())->toBeFalse()
        ->and(PayRunStatus::Approved->isLocked())->toBeFalse();
});

it('enforces valid status transitions', function (): void {
    expect(PayRunStatus::Draft->canTransitionTo(PayRunStatus::Preview))->toBeTrue()
        ->and(PayRunStatus::Draft->canTransitionTo(PayRunStatus::Finalized))->toBeFalse()
        ->and(PayRunStatus::Approved->canTransitionTo(PayRunStatus::Finalized))->toBeTrue()
        ->and(PayRunStatus::Finalized->canTransitionTo(PayRunStatus::Void))->toBeTrue()
        ->and(PayRunStatus::Void->canTransitionTo(PayRunStatus::Draft))->toBeFalse();
});

it('blocks updating a finalized pay run', function (): void {
    $run = PayRun::factory()->create(['status' => PayRunStatus::Finalized]);

    expect(fn () => $run->update(['total_gross' => 99999.00]))->toThrow(DomainException::class);
});

it('blocks updating a voided pay run', function (): void {
    $run = PayRun::factory()->create(['status' => PayRunStatus::Void]);

    expect(fn () => $run->update(['employee_count' => 1]))->toThrow(DomainException::class);
});

it('allows updating a draft pay run', function (): void {
    $run = PayRun::factory()->create(['status' => PayRunStatus::Draft]);
    $run->update(['employee_count' => 5]);

    expect($run->fresh()->employee_count)->toBe(5);
});

// ─── PayrollStatement user/profile consistency ────────────────────────────────

it('allows creating a statement when user matches profile', function (): void {
    $profile = PayrollEmployeeProfile::factory()->create();

    $statement = PayrollStatement::factory()->create([
        'user_id' => $profile->user_id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    expect($statement->exists)->toBeTrue();
});

it('blocks creating a statement when user does not match profile', function (): void {
    $profile = PayrollEmployeeProfile::factory()->create();
    $otherUser = User::factory()->create();

    expect(fn () => PayrollStatement::factory()->create([
        'user_id' => $otherUser->id,
        'payroll_employee_profile_id' => $profile->id,
    ]))->toThrow(DomainException::class);
});

it('blocks updating a statement to mismatch the profile user', function (): void {
    $profile = PayrollEmployeeProfile::factory()->create();
    $statement = PayrollStatement::factory()->create([
        'user_id' => $profile->user_id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    $otherUser = User::factory()->create();

    expect(fn () => $statement->update(['user_id' => $otherUser->id]))->toThrow(DomainException::class);
});

it('allows updating non-identity fields on a statement without triggering the guard', function (): void {
    $profile = PayrollEmployeeProfile::factory()->create();
    $statement = PayrollStatement::factory()->create([
        'user_id' => $profile->user_id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    $statement->update(['gross_pay' => 1500.00]);

    expect((float) $statement->fresh()->gross_pay)->toBe(1500.00);
});
