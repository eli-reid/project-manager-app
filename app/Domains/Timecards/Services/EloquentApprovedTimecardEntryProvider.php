<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Payroll\Contracts\ApprovedTimecardEntryProvider;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentApprovedTimecardEntryProvider implements ApprovedTimecardEntryProvider
{
    public function forPayPeriod(Carbon $payPeriodStart, Carbon $payPeriodEnd): Collection
    {
        return TimecardEntry::query()
            ->with(['timecard:id,status', 'project:id,is_prevailing_wage'])
            ->whereDate('date', '>=', $payPeriodStart->toDateString())
            ->whereDate('date', '<=', $payPeriodEnd->toDateString())
            ->whereHas('timecard', fn ($query) => $query->whereIn('status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED]))
            ->orderBy('date')
            ->get();
    }
}
