<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\GhostscriptPageTextExtractor;
use App\Domains\Plans\Services\PlanSheetMatcher;
use App\Domains\Plans\Services\PlanSheetMetadataExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        GhostscriptPageTextExtractor $textExtractor,
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
                ->each(function (PlanSheetRevision $revision) use ($extractor, $matcher, $path, $textExtractor): void {
                    $text = $textExtractor->extract($path, $revision->page_number);
                    $updatedRevision = $extractor->applyText($revision, $text);
                    $matchedSheet = $matcher->match($updatedRevision);

                    Log::info('ReindexPlanSetMetadataJob extracted revision metadata.', [
                        'plan_set_id' => $this->planSetId,
                        'revision_id' => $updatedRevision->id,
                        'page_number' => $updatedRevision->page_number,
                        'text_excerpt' => Str::limit(preg_replace('/\s+/', ' ', trim($text)) ?? '', 200),
                        'text_length' => strlen($text),
                        'detected_sheet_number' => $updatedRevision->detected_sheet_number,
                        'detected_title' => $updatedRevision->detected_title,
                        'detection_confidence' => $updatedRevision->detection_confidence,
                        'detection_source' => $updatedRevision->detection_source,
                        'matched_sheet_id' => $matchedSheet->id,
                        'matched_sheet_number' => $matchedSheet->sheet_number,
                    ]);
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
