<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Services\PlanSetRollbackService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SplitPlanSetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $planSetId) {}

    public function handle(PlanRasterizerContract $rasterizer, ?PlanSetRollbackService $rollbackService = null): void
    {
        $rollbackService ??= app(PlanSetRollbackService::class);
        $set = PlanSet::query()->with('sourceAsset')->find($this->planSetId);
        if ($set === null) {
            return;
        }
        if ($set->sourceAsset === null) {
            $rollbackService->rollback($set, 'Plan source asset is missing.');

            return;
        }
        try {
            $set->update(['status' => PlanSet::STATUS_SPLITTING]);
            $path = Storage::disk($set->sourceAsset->storage_disk)->path($set->sourceAsset->storage_path);
            $pageCount = $rasterizer->pageCount($path);
            if ($pageCount > (int) config('plans.max_pages_per_set', 500)) {
                throw new \RuntimeException('Plan set exceeds the maximum page limit.');
            }
            $set->update(['page_count' => $pageCount, 'status' => PlanSet::STATUS_RENDERING]);
            $jobs = [];
            foreach (range(1, $pageCount) as $page) {
                $sheet = PlanSheet::query()->create([
                    'project_id' => $set->project_id,
                    'discipline' => $set->discipline,
                ]);
                $revision = $sheet->revisions()->create(['plan_set_id' => $set->id, 'page_number' => $page]);
                $jobs[] = new RenderPlanPageJob($revision->id);
            }
            Bus::batch($jobs)
                ->then(fn (Batch $batch) => FinalizePlanSetJob::dispatch($this->planSetId))
                ->catch(function (Batch $batch, Throwable $exception) use ($set, $rollbackService): void {
                    $rollbackService->rollback($set, $exception->getMessage());
                })
                ->dispatch();
        } catch (Throwable $exception) {
            $rollbackService->rollback($set, $exception->getMessage());
        }
    }
}
