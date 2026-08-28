<?php

use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\Identity\Models\User;
use App\Core\Settings\Services\SettingsSqliteService;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Livewire\Livewire;

it('shows the pay rates widget on user edit when a payroll profile exists', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $managedUser->id]);
    $rateType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $rateType->id,
        'rate_amount' => 42.2500,
        'approved_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->assertSee('Pay Rates')
        ->assertSee('Add Pay Rate')
        ->assertSee('Standard');
});

it('adds a pay rate from the user edit widget', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();
    $profile = PayrollEmployeeProfile::factory()->create(['user_id' => $managedUser->id]);
    $rateType = PayRateType::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->set('new_pay_rate_type_id', (string) $rateType->id)
        ->set('new_rate_amount', '58.7500')
        ->set('new_effective_date', '2026-04-13')
        ->set('new_expiration_date', '')
        ->call('addPayRate')
        ->assertHasNoErrors();

    expect(PayRate::query()->where([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $rateType->id,
        'approved_by' => $admin->id,
    ])->exists())->toBeTrue();
});

it('creates a payroll profile from the user edit widget', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->assertSee('Create Payroll Profile')
        ->set('profile_employee_number', 'EMP-1001')
        ->set('profile_job_classification', 'Laborer')
        ->set('profile_ssn', '123-45-6789')
        ->set('profile_date_of_birth', '1990-01-01')
        ->set('profile_hire_date', '2026-01-01')
        ->set('profile_status', 'active')
        ->set('profile_pay_type', 'hourly')
        ->set('profile_sick_hours_allowance', '56')
        ->set('profile_vacation_hours_allowance', '120')
        ->set('profile_direct_deposit_active', true)
        ->call('createPayrollProfile')
        ->assertHasNoErrors();

    expect(PayrollEmployeeProfile::query()->where([
        'user_id' => $managedUser->id,
        'employee_number' => 'EMP-1001',
        'job_classification' => 'Laborer',
        'sick_hours_allowance' => 56.0,
        'vacation_hours_allowance' => 120.0,
    ])->exists())->toBeTrue();
});

it('creates a user and payroll profile in a single submission', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $role = Role::query()->create([
        'name' => 'Payroll Test Role',
        'description' => 'Role used for payroll user creation tests.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    Livewire::actingAs($admin)
        ->test(UserForm::class)
        ->set('first_name', 'Jordan')
        ->set('last_name', 'Payroll')
        ->set('username', 'jordan.payroll')
        ->set('email', 'jordan.payroll@example.test')
        ->set('phone', '555-0101')
        ->set('is_active', true)
        ->set('selectedRoleIds', [(string) $role->id])
        ->set('create_payroll_profile_on_save', true)
        ->set('profile_employee_number', 'EMP-3001')
        ->set('profile_job_classification', 'Foreman')
        ->set('profile_ssn', '123-45-6789')
        ->set('profile_date_of_birth', '1992-03-04')
        ->set('profile_hire_date', '2026-05-01')
        ->set('profile_status', 'active')
        ->set('profile_pay_type', 'hourly')
        ->set('profile_department', 'Field')
        ->set('profile_union_code', 'UN-1')
        ->set('profile_sick_hours_allowance', '40')
        ->set('profile_vacation_hours_allowance', '80')
        ->set('profile_direct_deposit_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $createdUser = User::query()->where('email', 'jordan.payroll@example.test')->first();

    expect($createdUser)->not->toBeNull();

    expect(PayrollEmployeeProfile::query()->where([
        'user_id' => $createdUser->id,
        'employee_number' => 'EMP-3001',
        'job_classification' => 'Foreman',
        'department' => 'Field',
        'union_code' => 'UN-1',
        'status' => 'active',
        'pay_type' => 'hourly',
        'sick_hours_allowance' => 40.0,
        'vacation_hours_allowance' => 80.0,
        'direct_deposit_active' => true,
    ])->exists())->toBeTrue();
});

it('validates payroll profile fields during single-step creation', function (): void {
    app(SettingsSqliteService::class)->set('payroll.employee_profile.ssn_required', 'true');

    $admin = User::factory()->create(['is_admin' => true]);
    $role = Role::query()->create([
        'name' => 'Payroll Validation Role',
        'description' => 'Role used for payroll validation tests.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    Livewire::actingAs($admin)
        ->test(UserForm::class)
        ->set('first_name', 'Jordan')
        ->set('last_name', 'Payroll')
        ->set('username', 'jordan.validation')
        ->set('email', 'jordan.validation@example.test')
        ->set('selectedRoleIds', [(string) $role->id])
        ->set('create_payroll_profile_on_save', true)
        ->set('profile_employee_number', '')
        ->set('profile_job_classification', '')
        ->set('profile_ssn', '')
        ->set('profile_date_of_birth', '')
        ->set('profile_hire_date', '')
        ->call('save')
        ->assertHasErrors([
            'profile_employee_number',
            'profile_job_classification',
            'profile_ssn',
            'profile_date_of_birth',
            'profile_hire_date',
        ]);
});

it('validates required fields when creating a payroll profile', function (): void {
    app(SettingsSqliteService::class)->set('payroll.employee_profile.ssn_required', 'true');

    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->set('profile_employee_number', '')
        ->set('profile_job_classification', '')
        ->set('profile_ssn', '')
        ->set('profile_date_of_birth', '')
        ->set('profile_hire_date', '')
        ->call('createPayrollProfile')
        ->assertHasErrors([
            'profile_employee_number',
            'profile_job_classification',
            'profile_ssn',
            'profile_date_of_birth',
            'profile_hire_date',
        ]);
});

it('allows creating payroll profile without ssn when setting disables requirement', function (): void {
    app(SettingsSqliteService::class)->set('payroll.employee_profile.ssn_required', 'false');

    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->set('profile_employee_number', 'EMP-1011')
        ->set('profile_job_classification', 'Laborer')
        ->set('profile_ssn', '')
        ->set('profile_date_of_birth', '1991-02-02')
        ->set('profile_hire_date', '2026-01-10')
        ->set('profile_status', 'active')
        ->set('profile_pay_type', 'hourly')
        ->call('createPayrollProfile')
        ->assertHasNoErrors();

    expect(PayrollEmployeeProfile::query()->where([
        'user_id' => $managedUser->id,
        'employee_number' => 'EMP-1011',
    ])->exists())->toBeTrue();
});

it('updates an existing payroll profile from the user edit widget', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $managedUser->id,
        'employee_number' => 'EMP-2001',
        'job_classification' => 'Operator',
        'status' => 'active',
        'pay_type' => 'hourly',
    ]);

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->assertSee('Edit Payroll Profile')
        ->set('profile_employee_number', 'EMP-2009')
        ->set('profile_job_classification', 'Foreman')
        ->set('profile_status', 'inactive')
        ->set('profile_pay_type', 'salary')
        ->set('profile_department', 'Field')
        ->set('profile_union_code', 'UN-7')
        ->set('profile_sick_hours_allowance', '72.5')
        ->set('profile_vacation_hours_allowance', '144')
        ->set('profile_direct_deposit_active', true)
        ->set('profile_ssn', '')
        ->call('updatePayrollProfile')
        ->assertHasNoErrors();

    $profile->refresh();

    expect($profile->employee_number)->toBe('EMP-2009')
        ->and($profile->job_classification)->toBe('Foreman')
        ->and($profile->status)->toBe('inactive')
        ->and($profile->pay_type)->toBe('salary')
        ->and($profile->department)->toBe('Field')
        ->and($profile->union_code)->toBe('UN-7')
        ->and($profile->sick_hours_allowance)->toBe(72.5)
        ->and($profile->vacation_hours_allowance)->toBe(144.0)
        ->and($profile->direct_deposit_active)->toBeTrue();
});

it('prevents adding a pay rate when the managed user has no payroll profile', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();
    $rateType = PayRateType::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $managedUser])
        ->set('new_pay_rate_type_id', (string) $rateType->id)
        ->set('new_rate_amount', '40.0000')
        ->set('new_effective_date', '2026-04-13')
        ->call('addPayRate')
        ->assertHasErrors(['pay_rates']);

    expect(PayRate::query()->count())->toBe(0);
});
