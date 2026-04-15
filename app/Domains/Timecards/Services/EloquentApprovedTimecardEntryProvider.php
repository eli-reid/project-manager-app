<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Payroll\Contracts\ApprovedTimecardEntryProvider;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class EloquentApprovedTimecardEntryProvider implements ApprovedTimecardEntryProvider
{
    public function forPayPeriod(CarbonImmutable $payPeriodStart, CarbonImmutable $payPeriodEnd): Collection
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
