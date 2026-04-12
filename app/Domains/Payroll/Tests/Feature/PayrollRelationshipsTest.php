<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Permissions\PayrollPermissions;
use App\Domains\Reports\Services\ReportRegistry;

it('keeps payroll persistence classes inside the payroll domain', function () {
    expect(file_exists(app_path('Domains/Payroll/Database/Migrations/2026_04_12_005043_create_payroll_employee_profiles_table.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Migrations/2026_04_12_020000_create_pay_runs_table.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Factories/PayrollEmployeeProfileFactory.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Factories/PayRunFactory.php')))->toBeTrue();
});

it('links pay runs to payroll statements', function () {
    $payRun = PayRun::factory()->create();
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $payRun->creator->id]);
    $statement = PayrollStatement::factory()->create([
        'pay_run_id' => $payRun->id,
        'user_id' => $payRun->creator->id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    expect($statement->payRun)->not->toBeNull()
        ->and($statement->payRun->is($payRun))->toBeTrue()
        ->and($payRun->payrollStatements)->toHaveCount(1)
        ->and($payRun->payrollStatements->first()?->is($statement))->toBeTrue();
});

it('links employee deductions through payroll profiles', function () {
    $profile = PayrollEmployeeProfile::factory()->create();
    $deduction = Deduction::factory()->create();

    $employeeDeduction = EmployeeDeduction::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'deduction_id' => $deduction->id,
    ]);

    expect($employeeDeduction->payrollEmployeeProfile->is($profile))->toBeTrue()
        ->and($employeeDeduction->deduction->is($deduction))->toBeTrue()
        ->and($profile->employeeDeductions)->toHaveCount(1)
        ->and($deduction->employeeDeductions)->toHaveCount(1);
});

it('links payroll profiles to payroll statements', function () {
    $profile = PayrollEmployeeProfile::factory()->create();

    $statement = PayrollStatement::factory()->create([
        'user_id' => $profile->user_id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    expect($profile->payrollStatements)->toHaveCount(1)
        ->and($statement->payrollEmployeeProfile->is($profile))->toBeTrue()
        ->and($statement->user->is($profile->user))->toBeTrue();
});

it('registers payroll report permissions', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    foreach (PayrollPermissions::all() as $permission) {
        expect(
            Permission::query()
                ->where('resource', $permission['resource'])
                ->where('action', $permission['action'])
                ->exists()
        )->toBeTrue();
    }
});

it('registers payroll report cards in financial reports registry', function () {
    $financialCards = collect(app(ReportRegistry::class)->forSection('financial'));

    expect($financialCards->pluck('key')->all())
        ->toContain('financial.payroll-certified-wh347')
        ->toContain('financial.payroll-tax-filings')
        ->toContain('financial.payroll-labor-cost')
        ->toContain('financial.payroll-union-remittance');
});

it('registers payroll report placeholder routes', function () {
    expect(route('reports.payroll.certified.index', absolute: false))->toBe('/reports/payroll/certified-wh347')
        ->and(route('reports.payroll.tax-filings.index', absolute: false))->toBe('/reports/payroll/tax-filings')
        ->and(route('reports.payroll.labor-cost.index', absolute: false))->toBe('/reports/payroll/labor-cost')
        ->and(route('reports.payroll.union-remittance.index', absolute: false))->toBe('/reports/payroll/union-remittance');
});
