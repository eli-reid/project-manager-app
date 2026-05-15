<?php

namespace App\Domains\Payroll\Services;

use App\Core\Settings\Services\WeekSettingsService;
use Illuminate\Support\Carbon;

class PayPeriodService
{
    /**
     * Returns the configured start (00:00) of the pay period containing the given date.
     * Pay periods always span seven days.
     */
    public function periodStartFor(Carbon $date): Carbon
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        return $date->copy()->startOfDay()->startOfWeek($weekStartsAt);
    }

    /**
     * Start of the current pay period.
     */
    public function currentPeriodStart(?Carbon $referenceDate = null): Carbon
    {
        return $this->periodStartFor(($referenceDate ?? Carbon::now())->copy());
    }

    /**
     * Start of the previous pay period (one week before current).
     */
    public function priorPeriodStart(?Carbon $referenceDate = null): Carbon
    {
        return $this->currentPeriodStart($referenceDate)->subWeek();
    }

    /**
     * Returns true if the date falls within the current or immediately prior pay period.
     */
    public function isWithinCurrentOrPriorPeriod(Carbon $date, ?Carbon $referenceDate = null): bool
    {
        $priorStart = $this->priorPeriodStart($referenceDate);
        $currentEnd = $this->currentPeriodStart($referenceDate)->addDays(6)->endOfDay();

        return $date->between($priorStart, $currentEnd);
    }

    /**
     * Returns true when a work date that falls in the prior pay period has missed the
     * submission cut-off. The cut-off is the configured start day that opens the current period at
     * 23:59:59 local time.
     */
    public function isBeyondCutOff(Carbon $workDate, ?Carbon $referenceDate = null): bool
    {
        $currentStart = $this->currentPeriodStart($referenceDate);

        // If the work date is in the current period there is no cut-off concern.
        if ($workDate->gte($currentStart)) {
            return false;
        }

        // Cut-off = current period start day at 23:59:59
        $cutOff = $currentStart->copy()->setTime(23, 59, 59);

        $comparisonNow = $referenceDate?->copy()->endOfDay() ?? Carbon::now();

        return $comparisonNow->gt($cutOff);
    }
}
