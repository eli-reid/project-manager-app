<?php

declare(strict_types=1);

namespace App\Domains\Plans\Console\Commands;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanThumbnailGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Backfills and recompresses small grid thumbnails for rendered revisions.
 */
final class RegeneratePlanThumbnailsCommand extends Command
{
    protected $signature = 'plans:regenerate-thumbnails {--chunk=50} {--force : Recompress all existing thumbnails}';

    protected $description = 'Regenerate small plan sheet thumbnails, optionally recompressing all existing derivatives.';

    public function handle(PlanThumbnailGenerator $thumbnails): int
    {
        $disk = Storage::disk(Settings::get('plans.storage_disk', 'local')->toString());
        $thumbnailExtension = Settings::get('plans.derivative_format', 'webp')->toString() === 'webp' && function_exists('imagewebp') ? 'webp' : 'png';
        $width = Settings::get('plans.thumbnail_width', 320)->toInt();
        $quality = Settings::get('plans.thumbnail_quality', 60)->toInt();
        $maxBytes = Settings::get('plans.thumbnail_max_bytes', 204800)->toInt();
        $force = (bool) $this->option('force');

        $regenerated = 0;
        $failed = 0;

        $query = PlanSheetRevision::query()->whereNotNull('preview_path');

        if (! $force) {
            $query->where(function ($query): void {
                $query->whereNull('thumbnail_path')
                    ->orWhereColumn('thumbnail_path', 'preview_path');
            });
        }

        $query->chunkById((int) $this->option('chunk'), function ($revisions) use ($disk, $thumbnails, $thumbnailExtension, $width, $quality, $maxBytes, &$regenerated, &$failed): void {
            foreach ($revisions as $revision) {
                try {
                    $directory = dirname($revision->preview_path);
                    $thumbnailPath = $directory.'/thumbnail.'.$thumbnailExtension;
                    $thumbnails->generate($disk->path($revision->preview_path), $disk->path($thumbnailPath), $width, $quality, $maxBytes);
                    $revision->update(['thumbnail_path' => $thumbnailPath]);
                    $regenerated++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->warn("Failed to regenerate thumbnail for revision [{$revision->id}]: {$exception->getMessage()}");
                }
            }
        });

        $this->info("Regenerated {$regenerated} thumbnail(s); {$failed} failure(s).");

        return self::SUCCESS;
    }
}
