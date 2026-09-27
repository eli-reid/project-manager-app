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
 * Backfills small grid thumbnails for revisions rendered before thumbnails were
 * split out as a real derivative, i.e. rows still shipping the full-resolution
 * preview PNG to the Plans tab grid.
 */
final class RegeneratePlanThumbnailsCommand extends Command
{
    protected $signature = 'plans:regenerate-thumbnails {--chunk=50}';

    protected $description = 'Regenerate small plan sheet thumbnails for revisions still pointing at the full-resolution preview.';

    public function handle(PlanThumbnailGenerator $thumbnails): int
    {
        $disk = Storage::disk(Settings::get('plans.storage_disk', 'local')->toString());
        $thumbnailExtension = Settings::get('plans.derivative_format', 'webp')->toString() === 'webp' && function_exists('imagewebp') ? 'webp' : 'png';
        $width = Settings::get('plans.thumbnail_width', 320)->toInt();

        $regenerated = 0;
        $failed = 0;

        PlanSheetRevision::query()
            ->whereNotNull('preview_path')
            ->where(function ($query): void {
                $query->whereNull('thumbnail_path')
                    ->orWhereColumn('thumbnail_path', 'preview_path');
            })
            ->chunkById((int) $this->option('chunk'), function ($revisions) use ($disk, $thumbnails, $thumbnailExtension, $width, &$regenerated, &$failed): void {
                foreach ($revisions as $revision) {
                    try {
                        $directory = dirname($revision->preview_path);
                        $thumbnailPath = $directory.'/thumbnail.'.$thumbnailExtension;
                        $thumbnails->generate($disk->path($revision->preview_path), $disk->path($thumbnailPath), $width);
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
