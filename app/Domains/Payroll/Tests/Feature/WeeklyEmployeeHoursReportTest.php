<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Livewire\Admin\Reports\WeeklyEmployeeHours;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function (): void {
    Settings::set('app.week_start_day', 'sunday');
});

it('allows authorized users to access weekly employee hours report', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.payroll.reports.weekly-employee-hours'))
        ->assertSuccessful()
        ->assertSee('Weekly Employee Hours');
});

it('forbids unauthorized users from accessing weekly employee hours report', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.payroll.reports.weekly-employee-hours'))
        ->assertForbidden();
});

it('displays approved timecards for selected week', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    $timecard = Timecard::factory()
        ->for($employee, 'user')
        ->create([
            'week_starting' => $weekStart,
            'week_ending' => $weekStart->endOfWeek(),
            'status' => Timecard::STATUS_APPROVED,
        ]);

    TimecardEntry::factory()
        ->for($timecard)
        ->for($employee, 'user')
        ->create(['hours' => 8.0, 'date' => $weekStart]);

    TimecardEntry::factory()
        ->for($timecard)
        ->for($employee, 'user')
        ->create(['hours' => 8.0, 'date' => $weekStart->addDay()]);

    $this->actingAs($admin)
        ->get(route('admin.payroll.reports.weekly-employee-hours', ['week_start' => $weekStart->toDateString()]))
        ->assertSuccessful()
        ->assertSee($employee->first_name)
        ->assertSee('16.00'); // Total hours
});

it('excludes rejected timecards from report', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    $timecard = Timecard::factory()
        ->for($employee, 'user')
        ->create([
            'week_starting' => $weekStart,
            'week_ending' => $weekStart->endOfWeek(),
            'status' => Timecard::STATUS_REJECTED,
        ]);

    TimecardEntry::factory()
        ->for($timecard)
        ->for($employee, 'user')
        ->create(['hours' => 8.0, 'date' => $weekStart]);

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => $weekStart->toDateString()])
        ->assertSet('employeeHours', fn ($hours) => $hours->count() === 0);
});

it('calculates total hours correctly', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee1 = User::factory()->create();
    $employee2 = User::factory()->create();
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    // Employee 1: 16 hours
    $timecard1 = Timecard::factory()
        ->for($employee1, 'user')
        ->create([
            'week_starting' => $weekStart,
            'status' => Timecard::STATUS_APPROVED,
        ]);
    TimecardEntry::factory()
        ->for($timecard1)
        ->for($employee1, 'user')
        ->create(['hours' => 8.0, 'date' => $weekStart]);
    TimecardEntry::factory()
        ->for($timecard1)
        ->for($employee1, 'user')
        ->create(['hours' => 8.0, 'date' => $weekStart->addDay()]);

    // Employee 2: 10 hours
    $timecard2 = Timecard::factory()
        ->for($employee2, 'user')
        ->create([
            'week_starting' => $weekStart,
            'status' => Timecard::STATUS_SUBMITTED,
        ]);
    TimecardEntry::factory()
        ->for($timecard2)
        ->for($employee2, 'user')
        ->create(['hours' => 10.0, 'date' => $weekStart->addDays(2)]);

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => $weekStart->toDateString()])
        ->assertSet('totalHours', 26.0);
});

it('allows navigation between weeks', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => $weekStart->toDateString()])
        ->call('nextWeek')
        ->assertSet('weekStart', $weekStart->addWeek()->startOfWeek(CarbonImmutable::SUNDAY)->toDateString())
        ->call('previousWeek')
        ->assertSet('weekStart', $weekStart->toDateString());
});

it('normalizes week boundaries using configured week start day', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    Settings::set('app.week_start_day', 'monday');

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => '2026-03-29'])
        ->assertSet('weekStart', '2026-03-23')
        ->assertSee('Week of Mar 23, 2026 to Mar 29, 2026');
});

it('loads weekly hours by entry dates even when stored week_starting differs from configured week start', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();

    // Simulate legacy data saved when Sunday was used as week_starting.
    $legacyWeekStart = CarbonImmutable::parse('2026-03-29');

    $timecard = Timecard::factory()
        ->for($employee, 'user')
        ->create([
            'week_starting' => $legacyWeekStart,
            'week_ending' => $legacyWeekStart->addDays(6),
            'status' => Timecard::STATUS_APPROVED,
        ]);

    TimecardEntry::factory()
        ->for($timecard)
        ->for($employee, 'user')
        ->create([
            'date' => '2026-03-30',
            'hours' => 8.0,
        ]);

    Settings::set('app.week_start_day', 'monday');

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => '2026-03-30'])
        ->assertSet('totalHours', 8.0)
        ->assertSee($employee->first_name);
});
