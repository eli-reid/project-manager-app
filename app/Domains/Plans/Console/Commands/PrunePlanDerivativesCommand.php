<?php

namespace App\Domains\Plans\Console\Commands;

use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class PrunePlanDerivativesCommand extends Command
{
    protected $signature = 'plans:prune-derivatives';

    protected $description = 'Remove derivative files no longer referenced by plan revisions.';

    public function handle(): int
    {
        $disk = Storage::disk((string) config('plans.storage_disk', 'local'));
        $referenced = PlanSheetRevision::query()->whereNotNull('preview_path')->pluck('preview_path')->merge(
            PlanSheetRevision::query()->whereNotNull('thumbnail_path')->pluck('thumbnail_path')
        )->all();
        $deleted = 0;
        foreach ($disk->allFiles('plans') as $file) {
            if (! in_array($file, $referenced, true)) {
                $disk->delete($file);
                $deleted++;
            }
        }
        $this->info("Deleted {$deleted} orphaned plan derivative(s).");

        return self::SUCCESS;
    }
}
