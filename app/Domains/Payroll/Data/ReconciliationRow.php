<?php

namespace App\Domains\Payroll\Data;

readonly class ReconciliationRow
{
    public function __construct(
        public string $userId,
        public ?string $projectId,
        public string $date,
        public float $timecardHours,
        public float $dailyHours,
        public float $variance,
        public bool $isMismatch,
    ) {}
}
