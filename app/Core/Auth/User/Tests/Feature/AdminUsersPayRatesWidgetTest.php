<?php

use App\Core\Auth\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\Identity\Models\User;
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
