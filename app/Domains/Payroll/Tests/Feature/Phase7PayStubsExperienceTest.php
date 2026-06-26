<?php

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Livewire\User\PayrollHistory\Show as PayrollHistoryShow;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use Livewire\Livewire;

it('shows payroll stub history for an authorized user and links to detail view', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $admin->id]);
    $payRun = PayRun::factory()->create([
        'pay_period_start' => '2026-04-12',
        'pay_period_end' => '2026-04-18',
        'pay_date' => '2026-04-25',
    ]);

    $statement = PayrollStatement::factory()->create([
        'user_id' => $admin->id,
        'payroll_employee_profile_id' => $profile->id,
        'pay_run_id' => $payRun->id,
        'gross_pay' => 1200.00,
        'net_pay' => 915.00,
    ]);

    $this->actingAs($admin)
        ->get(route('payroll.history'))
        ->assertSuccessful()
        ->assertSee('My Pay Stubs')
        ->assertSee('$1,200.00')
        ->assertSee(route('payroll.history.show', ['payrollStatement' => $statement]), false);
});

it('shows pay stub detail for an authorized user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $admin->id]);
    $payRun = PayRun::factory()->create([
        'pay_period_start' => '2026-04-12',
        'pay_period_end' => '2026-04-18',
        'pay_date' => '2026-04-25',
    ]);

    $statement = PayrollStatement::factory()->create([
        'user_id' => $admin->id,
        'payroll_employee_profile_id' => $profile->id,
        'pay_run_id' => $payRun->id,
        'gross_pay' => 1200.00,
        'federal_tax' => 120.00,
        'state_tax' => 40.00,
        'local_tax' => 10.00,
        'social_security' => 74.40,
        'medicare' => 17.40,
        'other_deductions' => 23.20,
        'net_pay' => 915.00,
    ]);

    $this->actingAs($admin)
        ->get(route('payroll.history.show', ['payrollStatement' => $statement]))
        ->assertSuccessful()
        ->assertSee('Pay Stub')
        ->assertSee('$915.00');
});

it('forbids non-admin users from pay stub pages when they do not have pay stub permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('payroll.history'))
        ->assertForbidden();
});

it('downloads a PDF pay stub from the pay stub detail component', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $admin->id]);
    $payRun = PayRun::factory()->create([
        'pay_period_start' => '2026-04-12',
        'pay_period_end' => '2026-04-18',
        'pay_date' => '2026-04-25',
    ]);

    $statement = PayrollStatement::factory()->create([
        'user_id' => $admin->id,
        'payroll_employee_profile_id' => $profile->id,
        'pay_run_id' => $payRun->id,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollHistoryShow::class, ['payrollStatement' => $statement])
        ->call('downloadPdf')
        ->assertFileDownloaded('pay-stub-20260425.pdf');
});
