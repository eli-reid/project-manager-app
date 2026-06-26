<?php

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Livewire\Admin\PayRateTypes\Index;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Livewire\Livewire;

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

// ─── PayRateType CRUD UI ──────────────────────────────────────────────────────

it('renders pay rate types index for admins', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    PayRateType::factory()->standard()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSuccessful()
        ->assertSee('Pay Rate Types')
        ->assertSee('New Rate Type');
});

it('forbids non-admin users from the pay rate types index', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openCreate')
        ->assertForbidden();
});

it('allows admins to create a custom pay rate type', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('formName', 'Prevailing Wage')
        ->set('formKey', 'prevailing_wage')
        ->set('formDescription', 'Used for public works.')
        ->set('formSortOrder', 10)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showFormModal', false);

    expect(PayRateType::where('key', 'prevailing_wage')->exists())->toBeTrue();
});

it('auto-generates the key from the name on create', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('formName', 'Special Rate Type')
        ->assertSet('formKey', 'special_rate_type');
});

it('validates that the key is unique on create', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    PayRateType::factory()->create(['key' => 'existing_key']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('formName', 'Duplicate')
        ->set('formKey', 'existing_key')
        ->call('save')
        ->assertHasErrors(['formKey']);
});

it('allows admins to edit a custom pay rate type', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $type = PayRateType::factory()->create(['name' => 'Old Name', 'key' => 'old_key']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEdit', $type->id)
        ->assertSet('formName', 'Old Name')
        ->assertSet('formKey', 'old_key')
        ->set('formName', 'New Name')
        ->set('formKey', 'new_key')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showFormModal', false);

    expect($type->fresh()->name)->toBe('New Name')
        ->and($type->fresh()->key)->toBe('new_key');
});

it('prevents changing the key of a system pay rate type', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $system = PayRateType::factory()->standard()->create();
    $originalKey = $system->key;

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEdit', $system->id)
        ->assertSet('isSystemType', true)
        ->set('formName', 'Updated System Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->key)->toBe($originalKey);
});

it('allows admins to delete a custom pay rate type with no associated rates', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $type = PayRateType::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('confirmDelete', $type->id)
        ->assertSet('deletingId', $type->id)
        ->call('deleteType')
        ->assertSet('showDeleteModal', false);

    expect(PayRateType::find($type->id))->toBeNull();
});

it('prevents deletion of a pay rate type that has associated employee rates', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $type = PayRateType::factory()->create();
    $profile = PayrollEmployeeProfile::factory()->create();
    PayRate::factory()->create([
        'pay_rate_type_id' => $type->id,
        'payroll_employee_profile_id' => $profile->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('confirmDelete', $type->id)
        ->call('deleteType')
        ->assertHasErrors(['delete']);

    expect(PayRateType::find($type->id))->not->toBeNull();
});
