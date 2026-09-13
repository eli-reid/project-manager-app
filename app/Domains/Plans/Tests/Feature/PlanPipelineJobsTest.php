<?php

declare(strict_types=1);

use App\Core\Assets\Models\Asset;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Jobs\RenderPlanPageJob;
use App\Domains\Plans\Jobs\SplitPlanSetJob;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Projects\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('uses the Batchable trait on RenderPlanPageJob', function (): void {
    $uses = class_uses_recursive(RenderPlanPageJob::class);

    expect($uses)->toContain(Batchable::class);
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
