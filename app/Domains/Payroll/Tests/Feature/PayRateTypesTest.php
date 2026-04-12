<?php

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;

it('stores typed standard rates per employee profile', function () {
    $standard = PayRateType::factory()->standard()->create();
    $employeeOne = PayrollEmployeeProfile::factory()->create();
    $employeeTwo = PayrollEmployeeProfile::factory()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $employeeOne->id,
        'pay_rate_type_id' => $standard->id,
        'project_id' => null,
        'rate_amount' => 45.0000,
    ]);
    PayRate::factory()->create([
        'payroll_employee_profile_id' => $employeeTwo->id,
        'pay_rate_type_id' => $standard->id,
        'project_id' => null,
        'rate_amount' => 20.0000,
    ]);

    $employeeOneStandardRate = PayRate::query()
        ->where('payroll_employee_profile_id', $employeeOne->id)
        ->where('pay_rate_type_id', $standard->id)
        ->value('rate_amount');

    $employeeTwoStandardRate = PayRate::query()
        ->where('payroll_employee_profile_id', $employeeTwo->id)
        ->where('pay_rate_type_id', $standard->id)
        ->value('rate_amount');

    expect((float) $employeeOneStandardRate)->toBe(45.0)
        ->and((float) $employeeTwoStandardRate)->toBe(20.0);
});

it('configures ssn as an encrypted attribute cast', function () {
    $casts = (new PayrollEmployeeProfile)->getCasts();

    expect($casts)->toHaveKey('ssn_encrypted')
        ->and($casts['ssn_encrypted'])->toBe('encrypted');
});

it('defines payroll reconciliation settings keys', function () {
    $definitions = require app_path('Domains/Payroll/config/settings.php');
    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)->toContain('payroll.reconciliation.enabled')
        ->toContain('payroll.reconciliation.hours_tolerance')
        ->toContain('payroll.reconciliation.require_project_match')
        ->toContain('payroll.reconciliation.require_cost_code_match')
        ->toContain('payroll.reconciliation.warning_only');
});
