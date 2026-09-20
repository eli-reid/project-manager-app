<?php

declare(strict_types=1);

use App\Core\Assets\Models\Asset;
use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Jobs\ReindexPlanSetMetadataJob;
use App\Domains\Plans\Jobs\RenderPlanPageJob;
use App\Domains\Plans\Jobs\SplitPlanSetJob;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Plans\Services\PlanSheetMetadataExtractor;
use App\Domains\Plans\Services\PlanSheetTextResolver;
use App\Domains\Projects\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('uses the Batchable trait on RenderPlanPageJob', function (): void {
    $uses = class_uses_recursive(RenderPlanPageJob::class);

    expect($uses)->toContain(Batchable::class);
});

it('matches sheet numbers in extracted PDF text', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $pattern = Settings::get('plans.sheet_number_pattern')->toString();
    preg_match_all('/'.trim($pattern, '/').'/i', 'FOR PRICING SCHEDULES H 0.02', $matches);

    expect($matches[0])->toContain('H 0.02');
});

it('applies OCR text without parsing the source PDF again', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');
    $revision = PlanSheetRevision::factory()->create();

    $updatedRevision = app(PlanSheetMetadataExtractor::class)->applyText($revision, 'SCHEDULES H 0.02');

    expect($updatedRevision->detected_sheet_number)->toBe('H 0.02')
        ->and($updatedRevision->detection_source)->toBe('text-layer');
});

it('applies a pre-computed OCR detection result including its source', function (): void {
    $revision = PlanSheetRevision::factory()->create();

    $updatedRevision = app(PlanSheetMetadataExtractor::class)->applyDetection($revision, [
        'sheet_number' => 'A 1.01',
        'title' => 'FLOOR PLAN',
        'confidence' => 0.9,
        'source' => 'google-vision',
        'text' => 'FLOOR PLAN A 1.01',
    ]);

    expect($updatedRevision->detected_sheet_number)->toBe('A 1.01')
        ->and($updatedRevision->detected_title)->toBe('FLOOR PLAN')
        ->and($updatedRevision->detection_source)->toBe('google-vision');
});

it('does not overwrite a manual detection with an OCR detection result', function (): void {
    $revision = PlanSheetRevision::factory()->create([
        'detection_source' => 'manual',
        'detected_sheet_number' => 'MANUAL-1',
    ]);

    $updatedRevision = app(PlanSheetMetadataExtractor::class)->applyDetection($revision, [
        'sheet_number' => 'A 1.01',
        'title' => 'FLOOR PLAN',
        'confidence' => 0.9,
        'source' => 'google-vision',
        'text' => 'FLOOR PLAN A 1.01',
    ]);

    expect($updatedRevision->detected_sheet_number)->toBe('MANUAL-1')
        ->and($updatedRevision->detection_source)->toBe('manual');
});

it('records reindexing failures on the plan set', function (): void {
    $planSet = PlanSet::factory()->create();

    (new ReindexPlanSetMetadataJob($planSet->id))->failed(new RuntimeException('Ghostscript is not available.'));

    expect($planSet->fresh()->error_message)->toBe('Sheet naming failed: Ghostscript is not available.');
});

it('writes extracted metadata to a json file while reindexing a rendered revision', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');
    Settings::set('plans.ocr_fallback_enabled', 'false');
    Storage::disk('local')->put('test-asset.pdf', 'pdf-content');

    $ghostscriptPath = storage_path('framework/testing/fake-gs.cmd');
    if (! is_dir(dirname($ghostscriptPath))) {
        mkdir(dirname($ghostscriptPath), 0755, true);
    }
    file_put_contents($ghostscriptPath, "@echo off\r\necho A-101 FLOOR PLAN\r\n");

    config()->set('plans.ghostscript_bin_path', $ghostscriptPath);

    $project = Project::factory()->create();
    $asset = Asset::factory()->create(['storage_disk' => 'local', 'storage_path' => 'test-asset.pdf']);
    $planSet = PlanSet::factory()->create([
        'project_id' => $project->id,
        'source_asset_id' => $asset->id,
    ]);
    $sheet = PlanSheet::factory()->create([
        'project_id' => $project->id,
        'sheet_number' => 'TEMP-1',
    ]);
    $revision = PlanSheetRevision::factory()->rendered()->create([
        'plan_set_id' => $planSet->id,
        'plan_sheet_id' => $sheet->id,
        'page_number' => 3,
    ]);

    (new ReindexPlanSetMetadataJob($planSet->id))->handle(
        app(PlanSheetMetadataExtractor::class),
        app(PlanSheetMatcher::class),
        app(PlanSheetTextResolver::class),
    );

    $outputPath = "plans/reindex-metadata/{$planSet->id}.json";

    Storage::disk('local')->assertExists($outputPath);

    $payload = json_decode(Storage::disk('local')->get($outputPath), true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['plan_set_id'])->toBe($planSet->id)
        ->and($payload[0]['revision_id'])->toBe($revision->id)
        ->and($payload[0]['page_number'])->toBe(3)
        ->and(trim($payload[0]['extracted_text']))->toBe('A-101 FLOOR PLAN')
        ->and($payload[0]['text_length'])->toBe(16)
        ->and($payload[0]['detected_sheet_number'])->toBe('A-101')
        ->and($payload[0]['detected_title'])->toBeNull()
        ->and((float) $payload[0]['detection_confidence'])->toBe(0.65)
        ->and($payload[0]['detection_source'])->toBe('text-layer')
        ->and($payload[0]['matched_sheet_id'])->toBe($sheet->id)
        ->and($payload[0]['matched_sheet_number'])->toBe('A-101');
});

it('dispatches batch jobs in SplitPlanSetJob without memory errors', function (): void {
    Bus::fake([RenderPlanPageJob::class]);

    $project = Project::factory()->create();
    $asset = Asset::factory()->create(['storage_disk' => 'local', 'storage_path' => 'test-asset.pdf']);
    Storage::disk('local')->put('test-asset.pdf', 'pdf-content');

    $planSet = PlanSet::factory()->create([
        'project_id' => $project->id,
        'source_asset_id' => $asset->id,
    ]);

    $rasterizer = Mockery::mock(PlanRasterizerContract::class);
    $rasterizer->shouldReceive('pageCount')->once()->andReturn(2);

    $job = new SplitPlanSetJob($planSet->id);
    $job->handle($rasterizer);

    expect($planSet->fresh()->status)->toBe(PlanSet::STATUS_RENDERING)
        ->and($planSet->fresh()->page_count)->toBe(2);

    Bus::assertBatchCount(1);
});
