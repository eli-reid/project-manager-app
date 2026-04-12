<?php

namespace App\Domains\Payroll\Services;

use Illuminate\Support\Carbon;

class PayPeriodService
{
    /**
     * Returns the start (Saturday 00:00) of the pay period containing the given date.
     * Pay periods run Saturday→Friday.
     */
    public function periodStartFor(Carbon $date): Carbon
    {
        $copy = $date->copy()->startOfDay();

        // Carbon: 0=Sunday, 1=Monday, …, 6=Saturday
        $dayOfWeek = (int) $copy->dayOfWeek;

        // Days to subtract to reach the most recent Saturday
        $daysBack = $dayOfWeek === 6 ? 0 : $dayOfWeek + 1;

        return $copy->subDays($daysBack);
    }

    /**
     * Start of the current pay period.
     */
    public function currentPeriodStart(): Carbon
    {
        return $this->periodStartFor(Carbon::now());
    }

    /**
     * Start of the previous pay period (one week before current).
     */
    public function priorPeriodStart(): Carbon
    {
        return $this->currentPeriodStart()->subWeek();
    }

    /**
     * Returns true if the date falls within the current or immediately prior pay period.
     */
    public function isWithinCurrentOrPriorPeriod(Carbon $date): bool
    {
        $priorStart = $this->priorPeriodStart();
        $currentEnd = $this->currentPeriodStart()->addDays(6)->endOfDay();

        return $date->between($priorStart, $currentEnd);
    }

    /**
     * Returns true when a work date that falls in the prior pay period has missed the
     * submission cut-off. The cut-off is the Saturday that opens the current period at
     * 23:59:59 local time.
     */
    public function isBeyondCutOff(Carbon $workDate): bool
    {
        $currentStart = $this->currentPeriodStart();

        // If the work date is in the current period there is no cut-off concern.
        if ($workDate->gte($currentStart)) {
            return false;
        }

        // Cut-off = current period's Saturday at 23:59:59
        $cutOff = $currentStart->copy()->setTime(23, 59, 59);

        return Carbon::now()->gt($cutOff);
    }
}
