<?php

declare(strict_types=1);

use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Jobs\FinalizePlanSetJob;
use App\Domains\Plans\Jobs\SplitPlanSetJob;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSetIngestionService;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;

it('creates a plan set with related sheets and rendered revisions', function (): void {
    $set = PlanSet::factory()->create(['page_count' => 4, 'processed_page_count' => 3]);
    $sheet = PlanSheet::factory()->create(['project_id' => $set->project_id]);
    $revision = PlanSheetRevision::factory()->rendered()->create([
        'plan_set_id' => $set->id,
        'plan_sheet_id' => $sheet->id,
        'is_current' => true,
    ]);
    $sheet->update(['current_revision_id' => $revision->id]);

    expect($set->fresh()->revisions)->toHaveCount(1)
        ->and($sheet->fresh()->currentRevision->is($revision))->toBeTrue()
        ->and($set->fresh()->progressPercent())->toBe(75);
});

it('casts annotation geometry and supports open filtering', function (): void {
    $annotation = PlanAnnotation::factory()->create();

    expect($annotation->geometry)->toBeArray()
        ->and(PlanAnnotation::query()->open()->sole()->is($annotation))->toBeTrue();
});

it('ingests a PDF as a Plans asset and dispatches splitting', function (): void {
    Bus::fake();
    $actor = User::factory()->create();
    $project = Project::factory()->create();

    $planSet = app(PlanSetIngestionService::class)->ingest(
        $project,
        $actor,
        UploadedFile::fake()->create('permit-set.pdf', 10, 'application/pdf'),
        ['name' => 'Permit Set'],
    );

    expect($planSet->source_asset_id)->not->toBeNull()
        ->and(AssetReference::query()->where('referencer_type', 'plans')->where('referencer_id', $planSet->id)->exists())->toBeTrue();
    Bus::assertDispatched(SplitPlanSetJob::class, fn (SplitPlanSetJob $job): bool => $job->planSetId === $planSet->id);
});

it('matches a detected revision to an existing sheet identity', function (): void {
    $project = Project::factory()->create();
    $set = PlanSet::factory()->create(['project_id' => $project->id]);
    $existing = PlanSheet::factory()->create(['project_id' => $project->id, 'sheet_number' => 'A-201']);
    $placeholder = PlanSheet::factory()->create(['project_id' => $project->id]);
    $revision = PlanSheetRevision::factory()->create([
        'plan_set_id' => $set->id,
        'plan_sheet_id' => $placeholder->id,
        'detected_sheet_number' => 'A-201',
    ]);

    $matched = app(PlanSheetMatcher::class)->match($revision);

    expect($matched->is($existing->fresh()))->toBeTrue()
        ->and($revision->fresh()->plan_sheet_id)->toBe($existing->id);
});

it('finalizes one current revision per sheet', function (): void {
    $set = PlanSet::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $set->project_id]);
    PlanSheetRevision::factory()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id]);
    PlanSheetRevision::factory()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id]);

    (new FinalizePlanSetJob($set->id))->handle(app(PlanSheetMatcher::class));

    expect($sheet->revisions()->where('is_current', true)->count())->toBe(1);
});
