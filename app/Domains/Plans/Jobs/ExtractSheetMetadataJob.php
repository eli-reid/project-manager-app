<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Core\Assets\Models\Asset;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSheetMetadataExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

final class ExtractSheetMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public string $revisionId) {}

    public function handle(PlanSheetMetadataExtractor $extractor): void
    {
        $revision = PlanSheetRevision::query()->with('set.sourceAsset')->findOrFail($this->revisionId);
        $asset = $revision->set?->sourceAsset;

        if (! $asset instanceof Asset) {
            throw new \RuntimeException('Plan source asset is missing.');
        }

        $path = Storage::disk($asset->storage_disk)->path($asset->storage_path);
        $extractor->extract($revision, $path);
    }
}
