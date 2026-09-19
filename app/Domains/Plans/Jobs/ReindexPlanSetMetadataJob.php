<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Plans\Services\PlanSheetMetadataExtractor;
use App\Domains\Plans\Services\PlanSheetTextResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ReindexPlanSetMetadataJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public string $planSetId) {}

    public function uniqueId(): string
    {
        return $this->planSetId;
    }

    public function handle(
        PlanSheetMetadataExtractor $extractor,
        PlanSheetMatcher $matcher,
        PlanSheetTextResolver $resolver,
    ): void {
        $set = PlanSet::query()->with('sourceAsset')->find($this->planSetId);
        if ($set?->sourceAsset === null) {
            return;
        }

        try {
            $path = Storage::disk($set->sourceAsset->storage_disk)->path($set->sourceAsset->storage_path);

            $set->revisions()
                ->where('status', 'rendered')
                ->orderBy('page_number')
                ->each(function (PlanSheetRevision $revision) use ($extractor, $matcher, $path, $resolver): void {
                    $updatedRevision = $extractor->applyDetection($revision, $resolver->resolve($path, $revision->page_number));
                    $matcher->match($updatedRevision);
                });

            $set->update(['error_message' => null]);
        } catch (Throwable $exception) {
            $set->update(['error_message' => 'Sheet naming failed: '.$exception->getMessage()]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        PlanSet::query()
            ->whereKey($this->planSetId)
            ->whereNull('error_message')
            ->update(['error_message' => 'Sheet naming failed: '.$exception->getMessage()]);
    }
}
