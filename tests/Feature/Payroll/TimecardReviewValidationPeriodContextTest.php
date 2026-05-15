<?php

use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Services\PayPeriodService;
use App\Domains\Payroll\Services\TimecardEntryValidationService;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Settings::set('app.week_start_day', 'monday');
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('uses configured settings for pay period start and seven day period length', function () {
    $service = app(PayPeriodService::class);

    $start = $service->periodStartFor(Carbon::parse('2026-04-09'));
    $end = $start->copy()->addDays(6);

    expect($start->toDateString())->toBe('2026-04-06')
        ->and($end->toDateString())->toBe('2026-04-12');
});

it('validates against the viewed payroll period context for historical review', function () {
    $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

    $entry = TimecardEntry::factory()->create([
        'user_id' => $profile->user_id,
        'project_id' => $project->id,
        'date' => '2026-04-21',
        'hours' => 8,
    ]);

    $service = app(TimecardEntryValidationService::class);

    $defaultBlocks = collect($service->validate($entry)->blocks())->pluck('ruleId')->toArray();
    $historyBlocks = collect($service->validate($entry, Carbon::parse('2026-04-20'))->blocks())->pluck('ruleId')->toArray();

    expect($defaultBlocks)->toContain('V-05')
        ->and($historyBlocks)->not->toContain('V-05');
});
