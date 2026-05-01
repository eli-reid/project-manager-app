<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Livewire\Admin\Reports\WeeklyEmployeeHours;
use App\Domains\Payroll\Livewire\Admin\Reports\WeeklyHourAdjustmentReport;
use App\Domains\Payroll\Models\WeeklyEmployeeHoursAdjustment;
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

it('allows authorized users to access weekly hour adjustment report', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.payroll.reports.weekly-hour-adjustments'))
        ->assertSuccessful()
        ->assertSee('Weekly Hour Adjustment Report');
});

it('forbids unauthorized users from accessing weekly hour adjustment report', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.payroll.reports.weekly-hour-adjustments'))
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

it('allows admin to adjust weekly employee hours without changing timecard entries', function (): void {
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

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => $weekStart->toDateString()])
        ->call('startEditing', $employee->id)
        ->set('editHours.'.$employee->id, '18.50')
        ->set('editReasons.'.$employee->id, 'Manual payroll correction for approved off-cycle work')
        ->call('saveAdjustment', $employee->id)
        ->assertHasNoErrors();

    $adjustment = WeeklyEmployeeHoursAdjustment::query()
        ->whereDate('week_start', $weekStart->toDateString())
        ->where('user_id', $employee->id)
        ->first();

    expect($adjustment)->not->toBeNull()
        ->and((float) $adjustment->source_hours)->toBe(16.0)
        ->and((float) $adjustment->adjusted_hours)->toBe(18.5)
        ->and($adjustment->reason)->toContain('Manual payroll correction');

    expect((float) TimecardEntry::query()
        ->where('user_id', $employee->id)
        ->sum('hours'))->toBe(16.0);

    $auditLog = AuditLog::query()
        ->where('action', 'payroll.weekly-hours.adjusted')
        ->where('target_id', (string) $adjustment->id)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->after['adjusted_hours'] ?? null)->toBe(18.5);
});

it('clears adjustment when adjusted hours are reset to source hours', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    WeeklyEmployeeHoursAdjustment::factory()->create([
        'week_start' => $weekStart->toDateString(),
        'user_id' => $employee->id,
        'source_hours' => 16.0,
        'adjusted_hours' => 18.0,
        'reason' => 'Initial correction',
        'edited_by_id' => $admin->id,
    ]);

    $timecard = Timecard::factory()
        ->for($employee, 'user')
        ->create([
            'week_starting' => $weekStart,
            'status' => Timecard::STATUS_APPROVED,
        ]);

    TimecardEntry::factory()
        ->for($timecard)
        ->for($employee, 'user')
        ->create(['hours' => 16.0, 'date' => $weekStart]);

    Livewire::actingAs($admin)
        ->test(WeeklyEmployeeHours::class, ['weekStart' => $weekStart->toDateString()])
        ->call('startEditing', $employee->id)
        ->set('editHours.'.$employee->id, '16.00')
        ->set('editReasons.'.$employee->id, 'Reset to source hours')
        ->call('saveAdjustment', $employee->id)
        ->assertHasNoErrors();

    expect(WeeklyEmployeeHoursAdjustment::query()
        ->whereDate('week_start', $weekStart->toDateString())
        ->where('user_id', $employee->id)
        ->exists())->toBeFalse();

    expect(AuditLog::query()->where('action', 'payroll.weekly-hours.adjustment.cleared')->exists())->toBeTrue();
});

it('shows weekly hour adjustments in dedicated report view', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employee = User::factory()->create();
    $weekStart = CarbonImmutable::parse('2026-03-30')->startOfWeek(CarbonImmutable::SUNDAY);

    WeeklyEmployeeHoursAdjustment::factory()->create([
        'week_start' => $weekStart->toDateString(),
        'user_id' => $employee->id,
        'source_hours' => 16.0,
        'adjusted_hours' => 18.5,
        'reason' => 'Payroll correction for approved manual work',
        'edited_by_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payroll.reports.weekly-hour-adjustments', ['week_start' => $weekStart->toDateString()]))
        ->assertSuccessful()
        ->assertSee($employee->first_name)
        ->assertSee('18.50')
        ->assertSee('Payroll correction for approved manual work');
});

it('filters weekly hour adjustments by year and employee with correct totals', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();

    WeeklyEmployeeHoursAdjustment::factory()->create([
        'week_start' => '2026-03-29',
        'user_id' => $employeeA->id,
        'source_hours' => 10.0,
        'adjusted_hours' => 12.0,
        'reason' => 'Yearly correction A',
        'edited_by_id' => $admin->id,
    ]);

    WeeklyEmployeeHoursAdjustment::factory()->create([
        'week_start' => '2026-05-10',
        'user_id' => $employeeB->id,
        'source_hours' => 5.0,
        'adjusted_hours' => 7.0,
        'reason' => 'Yearly correction B',
        'edited_by_id' => $admin->id,
    ]);

    WeeklyEmployeeHoursAdjustment::factory()->create([
        'week_start' => '2025-06-01',
        'user_id' => $employeeB->id,
        'source_hours' => 8.0,
        'adjusted_hours' => 9.0,
        'reason' => 'Prior year correction',
        'edited_by_id' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(WeeklyHourAdjustmentReport::class, ['year' => 2026])
        ->assertSee('Yearly correction A')
        ->assertSee('Yearly correction B')
        ->assertDontSee('Prior year correction')
        ->assertSee('15.00')
        ->assertSee('19.00')
        ->assertSee('4.00')
        ->set('employeeId', $employeeA->id)
        ->assertSee('Yearly correction A')
        ->assertDontSee('Yearly correction B')
        ->assertDontSee('Prior year correction')
        ->assertSee('10.00')
        ->assertSee('12.00')
        ->assertSee('2.00');
});
