<?php

use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;

it('keeps payroll persistence classes inside the payroll domain', function () {
    expect(file_exists(app_path('Domains/Payroll/Database/Migrations/2026_04_12_005043_create_payroll_employee_profiles_table.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Migrations/2026_04_12_020000_create_pay_runs_table.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Factories/PayrollEmployeeProfileFactory.php')))->toBeTrue()
        ->and(file_exists(app_path('Domains/Payroll/Database/Factories/PayRunFactory.php')))->toBeTrue();
});

it('links pay runs to payroll statements', function () {
    $payRun = PayRun::factory()->create();
    $statement = PayrollStatement::factory()->create([
        'pay_run_id' => $payRun->id,
        'user_id' => $payRun->creator->id,
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
