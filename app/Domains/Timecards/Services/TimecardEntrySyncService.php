<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Carbon;

class TimecardEntrySyncService
{
    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function sync(Timecard $timecard, array $entries, bool $pruneMissing = true): void
    {
        $persistedEntryIds = [];

        foreach ($entries as $entryData) {
            $entryId = isset($entryData['id']) ? (string) $entryData['id'] : null;
            $shouldDelete = (bool) ($entryData['delete'] ?? false);
            $hours = (float) ($entryData['hours'] ?? 0);

            if ($entryId !== null && $shouldDelete) {
                $timecard->entries()->whereKey($entryId)->delete();

                continue;
            }

            if ($hours <= 0) {
                continue;
            }

            $attributes = [
                'user_id' => $timecard->user_id,
                'project_id' => $entryData['project_id'] ?: null,
                'cost_code_id' => $entryData['cost_code_id'] ?: null,
                'custom_project_name' => $entryData['custom_project_name'] ?: null,
                'date' => Carbon::parse((string) $entryData['date'])->toDateString(),
                'start_time' => $entryData['start_time'] ?: null,
                'hours' => $hours,
                'notes' => $entryData['notes'] ?: null,
            ];

            if ($entryId !== null) {
                $entry = $timecard->entries()->whereKey($entryId)->first();

                if ($entry !== null) {
                    $entry->update($attributes);
                    $persistedEntryIds[] = (string) $entry->id;

                    continue;
                }
            }

            $entry = $timecard->entries()->create($attributes);
            $persistedEntryIds[] = (string) $entry->id;
        }

        if ($pruneMissing) {
            $query = $timecard->entries();

            if ($persistedEntryIds !== []) {
                $query->whereNotIn('id', $persistedEntryIds);
            }

            $query->delete();
        }

        $this->recalculateTotals($timecard);
    }

    public function recalculateTotals(Timecard $timecard): Timecard
    {
        $timecard->forceFill([
            'total_hours' => (float) $timecard->entries()->sum('hours'),
        ])->save();

        return $timecard->fresh();
    }
}
