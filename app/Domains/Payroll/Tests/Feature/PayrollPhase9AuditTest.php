<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Domains\Payroll\Livewire\User\Reports\Audit\Index as PayrollAuditIndex;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollAuditDigest;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Services\PayrollAuditTrailService;
use App\Domains\Payroll\Services\PayRunService;
use App\Domains\Payroll\Tasks\PayrollDigestValidationTask;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Livewire\Livewire;

it('registers payroll digest validation task type in the scheduler registry', function (): void {
    expect(app(TaskTypeRegistry::class)->resolve('payroll_audit_digest_validation'))
        ->toBe(PayrollDigestValidationTask::class);
});

it('records pay run mutations in core audit logs and digest chain', function (): void {
    $creator = User::factory()->create();
    $worker = User::factory()->create();

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $worker->id,
    ]);

    $standardType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 55,
        'effective_date' => now()->subMonth()->toDateString(),
        'approved_by' => $creator->id,
    ]);

    $project = Project::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $worker->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    $payRunService = app(PayRunService::class);

    $payRun = $payRunService->createPreview(
        now()->startOfWeek(),
        now()->endOfWeek(),
        now()->endOfWeek()->addWeek(),
        $creator->id,
    );

    $payRun = $payRunService->approve($payRun, $creator->id);
    $payRun = $payRunService->finalize($payRun);
    $payRunService->voidRun($payRun);

    $actions = AuditLog::query()
        ->where('action', 'like', 'payroll.pay-runs.%')
        ->pluck('action')
        ->all();

    expect($actions)
        ->toContain('payroll.pay-runs.preview-created')
        ->toContain('payroll.pay-runs.approved')
        ->toContain('payroll.pay-runs.finalized')
        ->toContain('payroll.pay-runs.voided');

    $digests = PayrollAuditDigest::query()
        ->whereHas('auditLog', fn ($query) => $query->where('action', 'like', 'payroll.pay-runs.%'))
        ->get();

    expect($digests)->not->toBeEmpty()
        ->and($digests->every(fn (PayrollAuditDigest $digest): bool => $digest->is_valid))->toBeTrue();
});

it('renders payroll audit report screen for users with payroll report view permission', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view']);

    app(PayrollAuditTrailService::class)->record('payroll.test.mutation', null, [
        'target_type' => 'payroll-test',
        'target_id' => 'example',
        'after' => ['status' => 'ok'],
    ]);

    $this->actingAs($user);

    Livewire::test(PayrollAuditIndex::class)
        ->assertSee('Payroll Audit Trail')
        ->assertSee('payroll.test.mutation');
});

it('alerts through audit events when digest validation finds invalid chain entries', function (): void {
    app(PayrollAuditTrailService::class)->record('payroll.test.chain-a', null, [
        'target_type' => 'payroll-test',
        'target_id' => 'a',
    ]);

    app(PayrollAuditTrailService::class)->record('payroll.test.chain-b', null, [
        'target_type' => 'payroll-test',
        'target_id' => 'b',
    ]);

    $digest = PayrollAuditDigest::query()->orderBy('created_at')->firstOrFail();
    $digest->update(['digest' => str_repeat('0', 64)]);

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'payroll_audit_digest_validation',
        'name' => 'Payroll Audit Digest Validation',
    ]);

    $scheduledTask = ScheduledTask::query()->create([
        'name' => 'Payroll Digest Validation',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '02:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'task_config' => [
            'chain_key' => 'payroll',
        ],
    ]);

    (new PayrollDigestValidationTask($scheduledTask))->dispatchJob();

    expect(AuditLog::query()->where('action', 'payroll.audit.digest-validation.alerted')->exists())
        ->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('payrollReportsUserWithPermissions')) {
    function payrollReportsUserWithPermissions(array $permissions): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Payroll Reports Test Role '.str()->uuid(),
            'description' => 'Role created by payroll reports tests',
            'is_active' => true,
            'built_in' => false,
            'access_level' => 25,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): ?string {
                [$resource, $action] = explode('.', $permission, 2);

                $permissionId = Permission::query()
                    ->where('resource', $resource)
                    ->where('action', $action)
                    ->value('id');

                return is_string($permissionId) ? $permissionId : null;
            })
            ->filter()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->sync([$role->id]);

        return $user->fresh();
    }
}
