<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\BurdenRate;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollCorrection;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollRecord;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Services\PayrollCalculationService;
use App\Domains\Payroll\Services\PayrollProcessingService;
use App\Domains\Payroll\Services\PayrollReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

// ─────────────────────────────────────────
// Permissions Registration
// ─────────────────────────────────────────

it('registers all payroll permissions in the system', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    foreach (['view', 'view-all', 'process', 'approve', 'export', 'manage', 'finalize', 'correct'] as $action) {
        expect(
            Permission::query()->where('resource', 'payroll')->where('action', $action)->exists()
        )->toBeTrue("Missing permission payroll.{$action}");
    }
});

// ─────────────────────────────────────────
// Policy Allow / Deny
// ─────────────────────────────────────────

it('denies guests from any payroll gate check', function (): void {
    expect(auth()->check())->toBeFalse();
    expect(Gate::allows('view', 'payroll'))->toBeFalse();
});

it('allows admin to bypass all payroll policy checks', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    expect(Gate::allows('view', 'payroll'))->toBeTrue();
    expect(Gate::allows('process', 'payroll'))->toBeTrue();
    expect(Gate::allows('finalize', 'payroll'))->toBeTrue();
});

it('denies user without payroll.view from viewing payroll', function (): void {
    $user = userWithPayrollPermissions([]);

    $this->actingAs($user);

    expect(Gate::allows('view', 'payroll'))->toBeFalse();
});

it('allows user with payroll.view permission to view payroll', function (): void {
    $user = userWithPayrollPermissions(['payroll.view']);

    $this->actingAs($user);

    expect(Gate::allows('view', 'payroll'))->toBeTrue();
});

it('denies user with only payroll.view from processing payroll', function (): void {
    $user = userWithPayrollPermissions(['payroll.view']);

    $this->actingAs($user);

    expect(Gate::allows('process', 'payroll'))->toBeFalse();
});

it('allows user with payroll.process to process payroll', function (): void {
    $user = userWithPayrollPermissions(['payroll.process']);

    $this->actingAs($user);

    expect(Gate::allows('process', 'payroll'))->toBeTrue();
});

it('allows user with payroll.export to export payroll', function (): void {
    $user = userWithPayrollPermissions(['payroll.export']);

    $this->actingAs($user);

    expect(Gate::allows('export', 'payroll'))->toBeTrue();
});

// ─────────────────────────────────────────
// Calculation Service
// ─────────────────────────────────────────

it('calculates regular pay correctly', function (): void {
    $user = User::factory()->create();
    $payRate = PayRate::factory()->create(['user_id' => $user->id, 'rate' => 20.00, 'effective_date' => now()->subDay()]);

    $result = app(PayrollCalculationService::class)->calculatePayroll(
        user: $user,
        regularHours: 40,
        overtimeHours: 0,
        payRate: $payRate,
        burdenRates: collect()
    );

    expect($result['gross_amount'])->toBe(800.00);
    expect($result['regular_hours'])->toBe(40.0);
    expect($result['overtime_hours'])->toBe(0.0);
    expect($result['total_deductions'])->toBe(0.0);
    expect($result['net_amount'])->toBe(800.00);
});

it('calculates overtime pay at 1.5x rate', function (): void {
    $user = User::factory()->create();
    $payRate = PayRate::factory()->create(['user_id' => $user->id, 'rate' => 20.00, 'effective_date' => now()->subDay()]);

    $result = app(PayrollCalculationService::class)->calculatePayroll(
        user: $user,
        regularHours: 40,
        overtimeHours: 10,
        payRate: $payRate,
        burdenRates: collect()
    );

    // 40h * $20 + 10h * $20 * 1.5 = $800 + $300 = $1100
    expect($result['gross_amount'])->toBe(1100.00);
    expect($result['overtime_hours'])->toBe(10.0);
});

it('applies percentage burden rates to calculate taxes', function (): void {
    $user = User::factory()->create();
    $payRate = PayRate::factory()->create(['user_id' => $user->id, 'rate' => 100.00, 'effective_date' => now()->subDay()]);
    $federalBurden = BurdenRate::factory()->create([
        'scope' => 'global',
        'component_name' => 'federal_tax',
        'percentage' => 22, // 22%
        'amount' => null,
        'effective_date' => now()->subDay(),
    ]);

    $result = app(PayrollCalculationService::class)->calculatePayroll(
        user: $user,
        regularHours: 10, // $1000 gross
        overtimeHours: 0,
        payRate: $payRate,
        burdenRates: collect([$federalBurden])
    );

    expect($result['gross_amount'])->toBe(1000.00);
    expect($result['federal_tax'])->toBe(220.00); // 22% of 1000
});

it('throws when no active pay rate exists for user', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(PayrollCalculationService::class)->calculatePayroll(
        user: $user,
        regularHours: 40,
        overtimeHours: 0,
    ))->toThrow(InvalidArgumentException::class, 'No active pay rate found for user');
});

it('validates calculation detects net/gross mismatches', function (): void {
    $result = app(PayrollCalculationService::class)->validateCalculation([
        'gross_amount' => 1000.00,
        'total_deductions' => 200.00,
        'net_amount' => 900.00, // Intentionally wrong
    ]);

    expect($result['valid'])->toBeFalse();
    expect($result['issues'])->toHaveKey('net_amount');
});

it('validates a correct calculation passes', function (): void {
    $result = app(PayrollCalculationService::class)->validateCalculation([
        'gross_amount' => 1000.00,
        'total_deductions' => 200.00,
        'net_amount' => 800.00,
    ]);

    expect($result['valid'])->toBeTrue();
    expect($result['issues'])->toBeEmpty();
});

// ─────────────────────────────────────────
// Period Lifecycle
// ─────────────────────────────────────────

it('creates a payroll period with open status', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $period = app(PayrollProcessingService::class)->createPayrollPeriod(
        periodStartDate: '2026-04-07',
        periodEndDate: '2026-04-13',
        createdBy: $admin
    );

    expect($period->status)->toBe(PayrollPeriod::STATUS_OPEN);
    expect($period->period_start_date->toDateString())->toBe('2026-04-07');
    expect($period->period_end_date->toDateString())->toBe('2026-04-13');
});

it('locks an open payroll period', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_OPEN]);

    app(PayrollProcessingService::class)->lockPayrollPeriod($period);

    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_LOCKED);
});

it('throws when locking a non-open period', function (): void {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_LOCKED]);

    expect(fn () => app(PayrollProcessingService::class)->lockPayrollPeriod($period))
        ->toThrow(ValidationException::class);
});

it('throws when finalizing a non-locked period', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_OPEN]);

    expect(fn () => app(PayrollProcessingService::class)->finalizePayrollPeriod($period, $admin))
        ->toThrow(ValidationException::class);
});

it('finalizes a locked period when all runs are approved', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_LOCKED]);
    PayRun::factory()->create([
        'payroll_period_id' => $period->id,
        'status' => PayRun::STATUS_APPROVED,
    ]);

    app(PayrollProcessingService::class)->finalizePayrollPeriod($period, $admin);

    $period->refresh();
    expect($period->status)->toBe(PayrollPeriod::STATUS_FINALIZED);
    expect($period->finalized_by)->toBe($admin->id);
    expect($period->finalized_at)->not->toBeNull();
});

it('throws when finalizing with unapproved pay runs', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_LOCKED]);
    PayRun::factory()->create([
        'payroll_period_id' => $period->id,
        'status' => PayRun::STATUS_PROVISIONAL,
    ]);

    expect(fn () => app(PayrollProcessingService::class)->finalizePayrollPeriod($period, $admin))
        ->toThrow(ValidationException::class);
});

it('approves a provisional pay run', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $payRun = PayRun::factory()->create(['status' => PayRun::STATUS_PROVISIONAL]);

    app(PayrollProcessingService::class)->approvePayRun($payRun, $admin);

    $payRun->refresh();
    expect($payRun->status)->toBe(PayRun::STATUS_APPROVED);
    expect($payRun->approved_by)->toBe($admin->id);
    expect($payRun->approved_at)->not->toBeNull();
});

it('throws when approving a non-provisional pay run', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $payRun = PayRun::factory()->create(['status' => PayRun::STATUS_DRAFT]);

    expect(fn () => app(PayrollProcessingService::class)->approvePayRun($payRun, $admin))
        ->toThrow(ValidationException::class);
});

it('finalizes all approved runs when period is finalized', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_LOCKED]);
    $payRun = PayRun::factory()->create([
        'payroll_period_id' => $period->id,
        'status' => PayRun::STATUS_APPROVED,
    ]);

    app(PayrollProcessingService::class)->finalizePayrollPeriod($period, $admin);

    expect($payRun->fresh()->status)->toBe(PayRun::STATUS_FINAL);
});

// ─────────────────────────────────────────
// Report Service
// ─────────────────────────────────────────

it('generates a payroll period summary with correct totals', function (): void {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_OPEN]);
    $payRun = PayRun::factory()->create(['payroll_period_id' => $period->id]);
    PayrollRecord::factory()->create([
        'pay_run_id' => $payRun->id,
        'gross_amount' => 1000.00,
        'total_deductions' => 300.00,
        'net_amount' => 700.00,
        'federal_tax' => 220.00,
        'state_tax' => 80.00,
        'local_tax' => 0.00,
        'social_security' => 62.00,
        'medicare' => 14.50,
    ]);

    $summary = app(PayrollReportService::class)->generatePeriodSummary($period);

    expect($summary['totals']['total_gross'])->toBe(1000.00);
    expect($summary['totals']['total_deductions'])->toBe(300.00);
    expect($summary['totals']['total_net'])->toBe(700.00);
    expect($summary['record_count'])->toBe(1);
    expect($summary['pay_run_count'])->toBe(1);
});

it('generates valid CSV output for a pay run', function (): void {
    $payRun = PayRun::factory()->create();
    PayrollRecord::factory()->create([
        'pay_run_id' => $payRun->id,
        'regular_hours' => 40,
        'overtime_hours' => 5,
        'gross_amount' => 950.00,
        'total_deductions' => 200.00,
        'net_amount' => 750.00,
    ]);

    $csv = app(PayrollReportService::class)->generatePayrollCSV($payRun);

    expect($csv)->toContain('Employee,Regular Hours');
    expect($csv)->toContain('40');
    expect($csv)->toContain('950');
    expect($csv)->toContain('750');
});

// ─────────────────────────────────────────
// Correction Workflow
// ─────────────────────────────────────────

it('creates a payroll correction with pending status', function (): void {
    $record = PayrollRecord::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $correction = PayrollCorrection::create([
        'payroll_record_id' => $record->id,
        'type' => PayrollCorrection::TYPE_ADJUSTMENT,
        'status' => PayrollCorrection::STATUS_PENDING,
        'amount' => 150.00,
        'description' => 'Missing bonus payment',
        'created_by' => $admin->id,
    ]);

    expect($correction->status)->toBe(PayrollCorrection::STATUS_PENDING);
    expect($correction->type)->toBe(PayrollCorrection::TYPE_ADJUSTMENT);
    expect($correction->payrollRecord->id)->toBe($record->id);
});

it('has pending scope filtering only pending corrections', function (): void {
    $record = PayrollRecord::factory()->create();

    PayrollCorrection::factory()->create(['payroll_record_id' => $record->id, 'status' => PayrollCorrection::STATUS_PENDING]);
    PayrollCorrection::factory()->create(['payroll_record_id' => $record->id, 'status' => PayrollCorrection::STATUS_APPROVED]);

    expect(PayrollCorrection::pending()->count())->toBe(1);
});

// ─────────────────────────────────────────
// PayRate Scopes
// ─────────────────────────────────────────

it('activeOn scope only returns rates valid on the given date', function (): void {
    $user = User::factory()->create();

    // Active now
    PayRate::factory()->create([
        'user_id' => $user->id,
        'effective_date' => now()->subMonth(),
        'end_date' => null,
    ]);

    // Expired
    PayRate::factory()->create([
        'user_id' => $user->id,
        'effective_date' => now()->subYear(),
        'end_date' => now()->subMonth(),
    ]);

    expect(PayRate::forUser($user->id)->activeOn()->count())->toBe(1);
});

// ─────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────

/**
 * @param  array<int, string>  $permissions
 */
function userWithPayrollPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Payroll Test Role '.str()->uuid(),
        'description' => 'Role for payroll domain tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
