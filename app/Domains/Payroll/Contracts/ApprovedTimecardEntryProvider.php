<?php

namespace App\Domains\Payroll\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ApprovedTimecardEntryProvider
{
    public function forPayPeriod(Carbon $payPeriodStart, Carbon $payPeriodEnd): Collection;
}
