<?php

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Enums\PayRunStatus;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Services\PayRunService;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;

it('creates a preview pay run and statements from submitted timecards', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create(['first_name' => 'Jordan', 'last_name' => 'Miles']);
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $employee->id]);
    $project = Project::factory()->create(['name' => 'Payroll Phase 6 Project']);

    $standardType = PayRateType::factory()->standard()->create();
    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'project_id' => null,
        'rate_amount' => 40,
        'effective_date' => '2026-04-01',
        'expiration_date' => null,
        'approved_by' => $admin->id,
    ]);

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => '2026-04-12',
        'week_ending' => '2026-04-18',
    ]);

    $timecard->entries()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'date' => '2026-04-14',
        'hours' => 8,
    ]);

    $service = app(PayRunService::class);

    $run = $service->createPreview('2026-04-12', '2026-04-18', '2026-04-25', $admin->id)->fresh();

    expect($run)->not->toBeNull()
        ->and($run?->status)->toBe(PayRunStatus::Preview)
        ->and((int) $run?->employee_count)->toBe(1)
        ->and((float) $run?->total_gross)->toBeGreaterThan(0)
        ->and((float) $run?->total_net)->toBeGreaterThan(0);

    $this->assertDatabaseHas('payroll_statements', [
        'pay_run_id' => $run?->id,
        'user_id' => $employee->id,
        'payroll_employee_profile_id' => $profile->id,
    ]);
});

it('requires approval before finalizing a pay run', function (): void {
    $run = PayRun::factory()->create([
        'status' => PayRunStatus::Preview,
        'approved_by' => null,
    ]);

    $service = app(PayRunService::class);

    expect(fn () => $service->finalize($run))
        ->toThrow(DomainException::class, 'Pay run must be approved before it can be finalized.');
});

it('supports approve finalize and void transitions in order', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $employee->id]);
    $project = Project::factory()->create();

    $standardType = PayRateType::factory()->standard()->create();
    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 35,
        'effective_date' => '2026-04-01',
        'approved_by' => $admin->id,
    ]);

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_APPROVED,
        'week_starting' => '2026-04-12',
        'week_ending' => '2026-04-18',
    ]);

    $timecard->entries()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'date' => '2026-04-15',
        'hours' => 10,
    ]);

    $service = app(PayRunService::class);
    $run = $service->createPreview('2026-04-12', '2026-04-18', '2026-04-25', $admin->id);

    $approved = $service->approve($run, $admin->id)->fresh();
    expect($approved?->status)->toBe(PayRunStatus::Approved)
        ->and((string) $approved?->approved_by)->toBe($admin->id);

    $finalized = $service->finalize($approved)->fresh();
    expect($finalized?->status)->toBe(PayRunStatus::Finalized)
        ->and($finalized?->finalized_at)->not->toBeNull();

    $voided = $service->voidRun($finalized)->fresh();
    expect($voided?->status)->toBe(PayRunStatus::Void);
});

it('allows admin users to access phase 6 pay run screens', function (string $routeName): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $run = PayRun::factory()->create();

    $routeParameters = match ($routeName) {
        'admin.payroll.runs.show' => ['payRun' => $run],
        default => [],
    };

    $this->actingAs($admin)
        ->get(route($routeName, $routeParameters))
        ->assertSuccessful();
})->with([
    'admin.payroll.runs.index',
    'admin.payroll.runs.create',
    'admin.payroll.runs.show',
]);

it('forbids non-admin users from phase 6 pay run screens without permissions', function (string $routeName): void {
    $user = User::factory()->create(['is_admin' => false]);
    $run = PayRun::factory()->create();

    $routeParameters = match ($routeName) {
        'admin.payroll.runs.show' => ['payRun' => $run],
        default => [],
    };

    $this->actingAs($user)
        ->get(route($routeName, $routeParameters))
        ->assertForbidden();
})->with([
    'admin.payroll.runs.index',
    'admin.payroll.runs.create',
    'admin.payroll.runs.show',
]);
