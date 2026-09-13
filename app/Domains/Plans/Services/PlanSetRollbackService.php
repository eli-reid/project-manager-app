<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class PlanSetRollbackService
{
    public function rollback(PlanSet $set, ?string $errorMessage = null): void
    {
        DB::transaction(function () use ($set, $errorMessage): void {
            $sheetIds = $set->revisions()->pluck('plan_sheet_id')->unique()->filter()->all();

            $set->revisions()->delete();

            if (! empty($sheetIds)) {
                PlanSheet::query()
                    ->whereIn('id', $sheetIds)
                    ->doesntHave('revisions')
                    ->forceDelete();
            }

            $diskName = Settings::get('plans.storage_disk', 'local')->toString();
            $disk = Storage::disk($diskName);
            $directory = "plans/{$set->project_id}/{$set->id}";

            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }

            $set->update([
                'status' => PlanSet::STATUS_FAILED,
                'processed_page_count' => 0,
                'error_message' => $errorMessage ?? 'Plan set processing failed and was rolled back.',
            ]);
        });
    }
}
