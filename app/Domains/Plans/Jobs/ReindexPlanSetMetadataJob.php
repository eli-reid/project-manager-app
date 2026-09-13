<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Plans\Services\PlanSheetMetadataExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

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

    public function handle(PlanSheetMetadataExtractor $extractor, PlanSheetMatcher $matcher): void
    {
        $set = PlanSet::query()->with('sourceAsset')->find($this->planSetId);
        if ($set?->sourceAsset === null) {
            return;
        }

        $path = Storage::disk($set->sourceAsset->storage_disk)->path($set->sourceAsset->storage_path);

        $set->revisions()
            ->where('status', 'rendered')
            ->orderBy('page_number')
            ->each(function (PlanSheetRevision $revision) use ($extractor, $matcher, $path): void {
                $extractor->extract($revision, $path);
                $matcher->match($revision->fresh());
            });
    }
}
