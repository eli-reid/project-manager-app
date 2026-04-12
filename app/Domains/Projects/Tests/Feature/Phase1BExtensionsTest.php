<?php

use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\QueryException;

// ─── Project Prevailing Wage Fields ──────────────────────────────────────────

it('stores is_prevailing_wage as false by default', function (): void {
    $project = Project::factory()->create();

    expect($project->is_prevailing_wage)->toBeFalse();
});

it('stores is_prevailing_wage and wage_determination_id on project', function (): void {
    $project = Project::factory()->create([
        'is_prevailing_wage' => true,
        'wage_determination_id' => 'CA-2026-0042',
    ]);

    expect($project->fresh()->is_prevailing_wage)->toBeTrue()
        ->and($project->fresh()->wage_determination_id)->toBe('CA-2026-0042');
});

it('allows a project to be prevailing wage without a determination id', function (): void {
    $project = Project::factory()->create([
        'is_prevailing_wage' => true,
        'wage_determination_id' => null,
    ]);

    expect($project->fresh()->is_prevailing_wage)->toBeTrue()
        ->and($project->fresh()->wage_determination_id)->toBeNull();
});

// ─── CostCode Model ───────────────────────────────────────────────────────────

it('creates a cost code belonging to a project', function (): void {
    $project = Project::factory()->create();
    $costCode = CostCode::factory()->for($project)->create([
        'code' => '16010',
        'description' => 'Rough-In Wiring',
    ]);

    expect($costCode->project_id)->toBe($project->id)
        ->and($costCode->code)->toBe('16010')
        ->and($costCode->description)->toBe('Rough-In Wiring')
        ->and($costCode->is_active)->toBeTrue();
});

it('enforces unique code per project', function (): void {
    $project = Project::factory()->create();
    CostCode::factory()->for($project)->create(['code' => '16010']);

    expect(fn () => CostCode::factory()->for($project)->create(['code' => '16010']))
        ->toThrow(QueryException::class);
});

it('allows the same code on different projects', function (): void {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    CostCode::factory()->for($projectA)->create(['code' => '16010']);
    $costCodeB = CostCode::factory()->for($projectB)->create(['code' => '16010']);

    expect($costCodeB->exists)->toBeTrue();
});

it('loads cost codes through the project relationship', function (): void {
    $project = Project::factory()->create();
    CostCode::factory()->for($project)->count(3)->create();

    expect($project->costCodes)->toHaveCount(3);
});

it('inactive factory state sets is_active false', function (): void {
    $costCode = CostCode::factory()->inactive()->create();

    expect($costCode->is_active)->toBeFalse();
});

// ─── TimecardEntry Payroll Fields ─────────────────────────────────────────────

it('stores cost_code_id on a timecard entry', function (): void {
    $project = Project::factory()->create();
    $costCode = CostCode::factory()->for($project)->create();
    $entry = TimecardEntry::factory()->create([
        'project_id' => $project->id,
        'cost_code_id' => $costCode->id,
    ]);

    expect($entry->cost_code_id)->toBe($costCode->id)
        ->and($entry->costCode->id)->toBe($costCode->id);
});

it('stores regular, overtime, and double-time hours on a timecard entry', function (): void {
    $entry = TimecardEntry::factory()->create([
        'regular_hours' => 8.0,
        'overtime_hours' => 2.5,
        'double_time_hours' => 0.0,
    ]);

    expect((float) $entry->regular_hours)->toBe(8.0)
        ->and((float) $entry->overtime_hours)->toBe(2.5)
        ->and((float) $entry->double_time_hours)->toBe(0.0);
});

it('stores prevailing wage fields on a timecard entry via factory state', function (): void {
    $entry = TimecardEntry::factory()
        ->withPrevailingWage('Journeyman Electrician')
        ->create();

    expect($entry->work_classification)->toBe('Journeyman Electrician')
        ->and($entry->prevailing_base_rate)->not->toBeNull()
        ->and($entry->prevailing_fringe_rate)->not->toBeNull()
        ->and($entry->fringe_payment_method)->toBeIn(['cash', 'plan']);
});

it('allows timecard entry with no payroll fields set', function (): void {
    $entry = TimecardEntry::factory()->create([
        'cost_code_id' => null,
        'regular_hours' => null,
        'overtime_hours' => null,
        'double_time_hours' => null,
        'work_classification' => null,
        'prevailing_base_rate' => null,
        'prevailing_fringe_rate' => null,
        'fringe_payment_method' => null,
    ]);

    expect($entry->cost_code_id)->toBeNull()
        ->and($entry->regular_hours)->toBeNull();
});
