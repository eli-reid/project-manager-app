<?php

namespace App\Domains\Timecards\Observers;

use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Services\TimecardEntrySyncService;

class TimecardEntryObserver
{
    public function __construct(private readonly TimecardEntrySyncService $timecardEntrySyncService) {}

    public function created(TimecardEntry $timecardEntry): void
    {
        $this->recalculate($timecardEntry);
    }

    public function updated(TimecardEntry $timecardEntry): void
    {
        $this->recalculate($timecardEntry);
    }

    public function deleted(TimecardEntry $timecardEntry): void
    {
        $this->recalculate($timecardEntry);
    }

    public function restored(TimecardEntry $timecardEntry): void
    {
        $this->recalculate($timecardEntry);
    }

    private function recalculate(TimecardEntry $timecardEntry): void
    {
        $timecard = $timecardEntry->timecard()->first();

        if ($timecard === null) {
            return;
        }

        $this->timecardEntrySyncService->recalculateTotals($timecard);
    }
}
