<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Support\Facades\DB;

final class PlanSheetMatcher
{
    public function match(PlanSheetRevision $revision): PlanSheet
    {
        return DB::transaction(function () use ($revision): PlanSheet {
            $revision->loadMissing('sheet', 'set');
            $detectedNumber = $revision->detected_sheet_number;

            if (blank($detectedNumber) || $revision->sheet->sheet_number === $detectedNumber) {
                return $revision->sheet;
            }

            $existingSheet = PlanSheet::query()
                ->where('project_id', $revision->set->project_id)
                ->where('sheet_number', $detectedNumber)
                ->lockForUpdate()
                ->first();

            if ($existingSheet === null || $existingSheet->is($revision->sheet)) {
                $revision->sheet->update([
                    'sheet_number' => $detectedNumber,
                    'title' => $revision->detected_title ?: $revision->sheet->title,
                ]);

                return $revision->sheet->fresh();
            }

            $revision->sheet()->associate($existingSheet);
            $revision->save();

            return $existingSheet;
        });
    }
}
