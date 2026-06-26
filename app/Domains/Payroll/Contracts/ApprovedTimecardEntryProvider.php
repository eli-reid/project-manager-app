<?php

namespace App\Domains\Payroll\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface ApprovedTimecardEntryProvider
{
    public function forPayPeriod(CarbonImmutable $payPeriodStart, CarbonImmutable $payPeriodEnd): Collection;
}
