<?php

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;

it('returns project timecard summary metrics', function (): void {
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $user = User::factory()->create();
    $timecard = Timecard::factory()->create(['user_id' => $user->id]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'hours' => 6,
        'regular_hours' => 4,
        'overtime_hours' => 2,
        'double_time_hours' => 0,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $otherProject->id,
        'hours' => 10,
        'regular_hours' => 8,
        'overtime_hours' => 2,
        'double_time_hours' => 0,
    ]);

    $summary = app(ProjectTimecardMetricsService::class)
        ->summaryForProject((string) $project->id);

    expect($summary['time_entry_count'])->toBe(2)
        ->and($summary['total_hours'])->toBe(14.0)
        ->and($summary['regular_hours'])->toBe(12.0)
        ->and($summary['overtime_hours'])->toBe(2.0)
        ->and($summary['double_time_hours'])->toBe(0.0);
});

it('returns project timecard detail metrics', function (): void {
    $project = Project::factory()->create();
    $userA = User::factory()->create(['first_name' => 'Alex', 'last_name' => 'Alpha']);
    $userB = User::factory()->create(['first_name' => 'Bree', 'last_name' => 'Beta']);

    $timecardA = Timecard::factory()->create(['user_id' => $userA->id]);
    $timecardB = Timecard::factory()->create(['user_id' => $userB->id]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecardA->id,
        'user_id' => $userA->id,
        'project_id' => $project->id,
        'date' => '2026-04-20',
        'hours' => 5,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecardB->id,
        'user_id' => $userB->id,
        'project_id' => $project->id,
        'date' => '2026-04-21',
        'hours' => 9,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecardA->id,
        'user_id' => $userA->id,
        'project_id' => $project->id,
        'date' => '2026-04-22',
        'hours' => 3,
    ]);

    $detail = app(ProjectTimecardMetricsService::class)
        ->detailForProject((string) $project->id, 2);

    expect($detail['recent_time_entries'])->toHaveCount(2)
        ->and((string) $detail['recent_time_entries']->first()->date->toDateString())->toBe('2026-04-22')
        ->and((string) $detail['recent_time_entries']->last()->date->toDateString())->toBe('2026-04-21');

    expect($detail['hours_by_user'])->toHaveCount(2)
        ->and((float) $detail['hours_by_user']->first()->total_hours)->toBe(9.0)
        ->and((float) $detail['hours_by_user']->last()->total_hours)->toBe(8.0);
});

it('falls back regular hours from total hours when breakdown columns are null', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();
    $timecard = Timecard::factory()->create(['user_id' => $user->id]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'hours' => 100,
        'regular_hours' => null,
        'overtime_hours' => null,
        'double_time_hours' => null,
    ]);

    $summary = app(ProjectTimecardMetricsService::class)
        ->summaryForProject((string) $project->id);

    expect($summary['time_entry_count'])->toBe(1)
        ->and($summary['total_hours'])->toBe(100.0)
        ->and($summary['regular_hours'])->toBe(100.0)
        ->and($summary['overtime_hours'])->toBe(0.0)
        ->and($summary['double_time_hours'])->toBe(0.0);
});

it('derives regular hours from total minus overtime and double time when regular is null', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();
    $timecard = Timecard::factory()->create(['user_id' => $user->id]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'hours' => 12,
        'regular_hours' => null,
        'overtime_hours' => 2,
        'double_time_hours' => 1,
    ]);

    $summary = app(ProjectTimecardMetricsService::class)
        ->summaryForProject((string) $project->id);

    expect($summary['total_hours'])->toBe(12.0)
        ->and($summary['regular_hours'])->toBe(9.0)
        ->and($summary['overtime_hours'])->toBe(2.0)
        ->and($summary['double_time_hours'])->toBe(1.0);
});
