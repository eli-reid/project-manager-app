<?php

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Livewire\Admin\PayRates\Form as AdminPayRateForm;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Http\Request;
use Livewire\Livewire;

it('allows admin users to access payroll admin pre-run screens', function (string $routeName): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $payRateType = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();
    $payRate = PayRate::factory()->create([
        'pay_rate_type_id' => $payRateType->id,
        'payroll_employee_profile_id' => $profile->id,
        'approved_by' => $admin->id,
    ]);

    $routeParameters = match ($routeName) {
        'admin.payroll.rates.edit' => ['payRate' => $payRate],
        default => [],
    };

    $this->actingAs($admin)
        ->get(route($routeName, $routeParameters))
        ->assertSuccessful();
})->with([
    'admin.payroll.rate-types.index',
    'admin.payroll.rates.index',
    'admin.payroll.rates.create',
    'admin.payroll.rates.edit',
    'admin.payroll.timecards.review',
]);

it('creates a pay rate through the payroll admin rate form', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $profile = PayrollEmployeeProfile::factory()->create();
    $rateType = PayRateType::factory()->standard()->create();

    $this->actingAs($admin);

    Livewire::test(AdminPayRateForm::class)
        ->set('payroll_employee_profile_id', (string) $profile->id)
        ->set('pay_rate_type_id', (string) $rateType->id)
        ->set('rate_amount', '47.2500')
        ->set('effective_date', '2026-04-12')
        ->set('expiration_date', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.payroll.rates.index'));

    $this->assertDatabaseHas('pay_rates', [
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $rateType->id,
        'rate_amount' => 47.25,
        'approved_by' => $admin->id,
    ]);
});

it('shows submitted entries on the payroll timecard review screen', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create(['first_name' => 'Riley', 'last_name' => 'Stone']);
    $project = Project::factory()->create(['name' => 'Review Project']);

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => '2026-04-12',
        'week_ending' => '2026-04-18',
    ]);

    $timecard->entries()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'date' => '2026-04-13',
        'hours' => 8,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payroll.timecards.review', ['week' => '2026-04-12']))
        ->assertSuccessful()
        ->assertSee('Payroll Timecard Review')
        ->assertSee('Riley Stone')
        ->assertSee('Review Project');
});

it('shows a direct timecard review link in the payroll layout', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.payroll.rates.index'))
        ->assertSuccessful()
        ->assertSee('Timecard Review')
        ->assertSee(route('admin.payroll.timecards.review'), false);
});

it('shows a direct required users link in the payroll layout', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.payroll.rates.index'))
        ->assertSuccessful()
        ->assertSee('Required Users')
        ->assertSee(route('admin.timecards.required-users'), false);
});

it('highlights required users without highlighting timecards on required users route', function (): void {
    $request = Request::create(route('admin.timecards.required-users'), 'GET');
    $route = app('router')->getRoutes()->match($request);
    $request->setRouteResolver(static fn () => $route);
    app()->instance('request', $request);

    $timecardsCurrent = request()->routeIs('admin.timecards.*')
        && ! request()->routeIs('admin.timecards.required-users*');
    $requiredUsersCurrent = request()->routeIs('admin.timecards.required-users*');

    expect($requiredUsersCurrent)->toBeTrue()
        ->and($timecardsCurrent)->toBeFalse();
});

it('forbids non-admin users from payroll admin pre-run screens without explicit permissions', function (string $routeName): void {
    $user = User::factory()->create(['is_admin' => false]);

    $payRateType = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();
    $payRate = PayRate::factory()->create([
        'pay_rate_type_id' => $payRateType->id,
        'payroll_employee_profile_id' => $profile->id,
        'approved_by' => User::factory()->create(['is_admin' => true])->id,
    ]);

    $routeParameters = match ($routeName) {
        'admin.payroll.rates.edit' => ['payRate' => $payRate],
        default => [],
    };

    $this->actingAs($user)
        ->get(route($routeName, $routeParameters))
        ->assertForbidden();
})->with([
    'admin.payroll.rate-types.index',
    'admin.payroll.rates.index',
    'admin.payroll.rates.create',
    'admin.payroll.rates.edit',
    'admin.payroll.timecards.review',
]);
