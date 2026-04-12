<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Data\OvertimeBreakdown;
use App\Domains\Payroll\Enums\OvertimeRule;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Collection;

class OvertimeCalculationService
{
    /**
     * Calculate regular / overtime / double-time breakdowns for a collection of
     * timecard entries that all belong to the same employee within one pay week
     * (Saturday–Friday).
     *
     * @param  Collection<int, TimecardEntry>  $entries  All entries for one employee/week, may be unsorted.
     * @return array<string, OvertimeBreakdown> Keyed by TimecardEntry id (cast to string).
     */
    public function calculate(Collection $entries, OvertimeRule $rule): array
    {
        $sorted = $entries->sortBy(fn (TimecardEntry $e): string => $e->date->toDateString())->values();

        return match ($rule) {
            OvertimeRule::WeeklyFlsa => $this->calculateWeeklyFlsa($sorted),
            OvertimeRule::CaliforniaDaily => $this->calculateCaliforniaDaily($sorted),
        };
    }

    // ------------------------------------------------------------------
    // Weekly FLSA (40-hour threshold)
    // ------------------------------------------------------------------

    /**
     * @param  Collection<int, TimecardEntry>  $entries  Sorted ascending by date.
     * @return array<string, OvertimeBreakdown>
     */
    private function calculateWeeklyFlsa(Collection $entries): array
    {
        $result = [];
        $runningTotal = 0.0;

        foreach ($entries as $entry) {
            $hours = (float) $entry->hours;
            $remainingRegular = max(0.0, 40.0 - $runningTotal);
            $regularHours = min($hours, $remainingRegular);
            $overtimeHours = $hours - $regularHours;
            $runningTotal += $hours;

            $result[(string) $entry->id] = new OvertimeBreakdown(
                regularHours: $regularHours,
                overtimeHours: $overtimeHours,
                doubleTimeHours: 0.0,
            );
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // California Daily (8/12-hour thresholds + 7th consecutive-day rule)
    // ------------------------------------------------------------------

    /**
     * @param  Collection<int, TimecardEntry>  $entries  Sorted ascending by date.
     * @return array<string, OvertimeBreakdown>
     */
    private function calculateCaliforniaDaily(Collection $entries): array
    {
        // Identify which dates have any worked hours (for 7th-day detection).
        $workedDates = $entries
            ->map(fn (TimecardEntry $e): string => $e->date->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $result = [];

        foreach ($entries as $entry) {
            $hours = (float) $entry->hours;
            $dateStr = $entry->date->toDateString();

            if ($this->isSeventhConsecutiveDay($dateStr, $workedDates)) {
                // 7th-day rule overrides the daily split.
                $result[(string) $entry->id] = new OvertimeBreakdown(
                    regularHours: 0.0,
                    overtimeHours: min(8.0, $hours),
                    doubleTimeHours: max(0.0, $hours - 8.0),
                );
            } else {
                $result[(string) $entry->id] = $this->dailySplit($hours);
            }
        }

        return $result;
    }

    /**
     * California 8/12-hour daily split.
     */
    private function dailySplit(float $hours): OvertimeBreakdown
    {
        if ($hours > 12.0) {
            return new OvertimeBreakdown(
                regularHours: 8.0,
                overtimeHours: 4.0,
                doubleTimeHours: $hours - 12.0,
            );
        }

        if ($hours > 8.0) {
            return new OvertimeBreakdown(
                regularHours: 8.0,
                overtimeHours: $hours - 8.0,
                doubleTimeHours: 0.0,
            );
        }

        return new OvertimeBreakdown(
            regularHours: $hours,
            overtimeHours: 0.0,
            doubleTimeHours: 0.0,
        );
    }

    /**
     * Returns true when $dateStr is the 7th consecutive worked day in the week,
     * meaning all 6 preceding calendar days in $workedDates are consecutive.
     *
     * @param  array<int, string>  $workedDates  All worked dates in the week, sorted ascending.
     */
    private function isSeventhConsecutiveDay(string $dateStr, array $workedDates): bool
    {
        $position = array_search($dateStr, $workedDates, true);

        if ($position === false || $position < 6) {
            return false;
        }

        // The 6 preceding entries must be exactly consecutive calendar days.
        for ($i = (int) $position - 5; $i <= (int) $position; $i++) {
            $expected = date('Y-m-d', strtotime($workedDates[$i - 1]) + 86400);
            if ($workedDates[$i] !== $expected) {
                return false;
            }
        }

        return true;
    }
}
