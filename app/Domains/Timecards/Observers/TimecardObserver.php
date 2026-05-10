<?php

namespace App\Domains\Timecards\Observers;

use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Cache;

class TimecardObserver
{
    /**
     * Handle the Timecard "updated" event.
     * Clears relevant caches when timecard status changes.
     */
    public function updated(Timecard $timecard): void
    {
        if ($timecard->isDirty('status')) {
            $this->invalidateCaches($timecard);
        }
    }

    /**
     * Handle the Timecard "deleted" event.
     */
    public function deleted(Timecard $timecard): void
    {
        $this->invalidateCaches($timecard);
    }

    /**
     * Invalidate timecard-related caches.
     */
    private function invalidateCaches(Timecard $timecard): void
    {
        // Clear user's timecard dashboard cache
        Cache::forget("user.{$timecard->user_id}.timecards");

        // Clear timecard-specific cache
        Cache::forget("timecard.{$timecard->id}");

        // Clear reminder cache when status changes (submitted/approved/rejected)
        if (in_array($timecard->status, [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED, Timecard::STATUS_REJECTED])) {
            Cache::forget("timecards.reminders.user.{$timecard->user_id}");
        }
    }
}
