<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class RenderPlanPageJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public string $revisionId) {}

    public function uniqueId(): string
    {
        return $this->revisionId;
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(PlanRasterizerContract $rasterizer): void
    {
        $revision = PlanSheetRevision::query()->with('set.sourceAsset')->find($this->revisionId);
        if ($revision === null || $revision->set?->sourceAsset === null) {
            return;
        }
        try {
            $asset = $revision->set->sourceAsset;
            $disk = Storage::disk(Settings::get('plans.storage_disk', 'local')->toString());
            $directory = "plans/{$revision->set->project_id}/{$revision->set_id}/{$revision->page_number}";
            $disk->makeDirectory($directory);
            $path = $disk->path($directory);
            $rasterizer->renderPage(Storage::disk($asset->storage_disk)->path($asset->storage_path), $revision->page_number, Settings::get('plans.render_dpi', 150)->toInt(), $path.'/preview.png');
            $revision->update(['preview_path' => $directory.'/preview.png', 'thumbnail_path' => $directory.'/preview.png', 'status' => 'rendered', 'width' => null, 'height' => null]);
            $revision->set()->update(['processed_page_count' => DB::raw('processed_page_count + 1')]);
        } catch (Throwable $exception) {
            $revision->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $revision = PlanSheetRevision::query()->with('set')->find($this->revisionId);

        if ($revision === null) {
            return;
        }

        $revision->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
        $revision->set?->update([
            'status' => PlanSet::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
