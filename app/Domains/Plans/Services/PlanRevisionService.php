<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Support\Facades\DB;

final class PlanRevisionService
{
    public function publish(PlanSheet $sheet, PlanSheetRevision $revision): PlanSheetRevision
    {
        abort_unless($revision->plan_sheet_id === $sheet->id, 404);

        return DB::transaction(function () use ($sheet, $revision): PlanSheetRevision {
            $lockedSheet = PlanSheet::query()->whereKey($sheet->id)->lockForUpdate()->firstOrFail();
            $lockedSheet->revisions()->where('id', '!=', $revision->id)->update(['is_current' => false]);
            $revision->update(['is_current' => true, 'published_at' => now()]);
            $lockedSheet->update(['current_revision_id' => $revision->id]);

            return $revision->fresh();
        });
    }
}
