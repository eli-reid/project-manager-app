<?php

declare(strict_types=1);

use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Jobs\FinalizePlanSetJob;
use App\Domains\Plans\Jobs\SplitPlanSetJob;
use App\Domains\Plans\Livewire\Sheets\Viewer;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Models\PlanViewState;
use App\Domains\Plans\Services\PlanGeometryService;
use App\Domains\Plans\Services\PlanRevisionService;
use App\Domains\Plans\Services\PlanSetIngestionService;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Plans\Services\PlanSheetMetadataOverrideService;
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

it('preserves manual metadata overrides when applying sheet metadata', function (): void {
    $revision = PlanSheetRevision::factory()->create([
        'detected_sheet_number' => 'A-101',
        'detected_title' => 'Existing title',
        'detection_source' => 'text-layer',
    ]);

    $updated = app(PlanSheetMetadataOverrideService::class)->apply($revision, 'a-201', 'Reflected ceiling plan');

    expect($updated->detected_sheet_number)->toBe('A-201')
        ->and($updated->detected_title)->toBe('Reflected ceiling plan')
        ->and($updated->detection_source)->toBe('manual')
        ->and((float) $updated->detection_confidence)->toBe(1.0);
});

it('normalizes annotation geometry to the unit square', function (): void {
    $geometry = app(PlanGeometryService::class)->normalize([
        'points' => [[-1, 0.25], [2, 0.75]],
        'x' => 1.4,
        'y' => -0.2,
        'width' => 0.5,
    ]);

    expect($geometry['points'])->toEqual([[0, 0.25], [1, 0.75]])
        ->and((float) $geometry['x'])->toBe(1.0)
        ->and((float) $geometry['y'])->toBe(0.0)
        ->and((float) $geometry['width'])->toBe(0.5);
});

it('finalizes one current revision per sheet', function (): void {
    $set = PlanSet::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $set->project_id]);
    PlanSheetRevision::factory()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id]);
    PlanSheetRevision::factory()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id]);

    (new FinalizePlanSetJob($set->id))->handle(app(PlanSheetMatcher::class));

    expect($sheet->revisions()->where('is_current', true)->count())->toBe(1);
});

it('marks a plan set failed when a revision failed to render', function (): void {
    $set = PlanSet::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $set->project_id]);
    PlanSheetRevision::factory()->create([
        'plan_set_id' => $set->id,
        'plan_sheet_id' => $sheet->id,
        'status' => 'failed',
        'error_message' => 'Renderer failed',
    ]);

    (new FinalizePlanSetJob($set->id))->handle(app(PlanSheetMatcher::class));

    expect($set->fresh()->status)->toBe(PlanSet::STATUS_FAILED)
        ->and($set->fresh()->error_message)->toBe('One or more plan pages failed to render.');
});

it('publishes a selected revision and demotes the previous current revision', function (): void {
    $set = PlanSet::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $set->project_id]);
    $current = PlanSheetRevision::factory()->rendered()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id, 'is_current' => true]);
    $older = PlanSheetRevision::factory()->rendered()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id, 'is_current' => false]);
    $sheet->update(['current_revision_id' => $current->id]);

    app(PlanRevisionService::class)->publish($sheet, $older);

    expect($sheet->fresh()->current_revision_id)->toBe($older->id)
        ->and($sheet->revisions()->where('is_current', true)->pluck('id')->all())->toBe([$older->id]);
});

it('restores and persists viewer state for the authenticated user', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $sheet = PlanSheet::factory()->create();
    $revision = PlanSheetRevision::factory()->rendered()->create([
        'plan_sheet_id' => $sheet->id,
        'plan_set_id' => PlanSet::factory()->create(['project_id' => $sheet->project_id])->id,
        'is_current' => true,
    ]);
    $sheet->update(['current_revision_id' => $revision->id]);
    $this->actingAs($user);

    $viewer = new Viewer;
    $viewer->sheet = $sheet;
    $viewer->persistViewState(2.5, 1.4, -0.2);

    $state = PlanViewState::query()->where('user_id', $user->id)->where('plan_sheet_id', $sheet->id)->firstOrFail();

    expect((float) $state->zoom)->toBe(2.5)
        ->and((float) $state->center_x)->toBe(1.0)
        ->and((float) $state->center_y)->toBe(0.0);
});
