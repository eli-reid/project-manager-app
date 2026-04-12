<?php

namespace App\Domains\Payroll\Data;

readonly class OvertimeBreakdown
{
    public function __construct(
        public float $regularHours,
        public float $overtimeHours,
        public float $doubleTimeHours,
    ) {}

    public function totalHours(): float
    {
        return $this->regularHours + $this->overtimeHours + $this->doubleTimeHours;
    }
}
