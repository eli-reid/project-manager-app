<?php

namespace App\Domains\Payroll\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface PayrollTimecardReadGateway
{
    public function approvedEntriesForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId = null,
        array $statuses = [],
        array $with = [],
    ): Collection;

    public function reviewEntriesForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $userId = null,
        ?string $projectId = null,
    ): Collection;

    public function existingHoursForUserOnDate(string $userId, Carbon $date, ?string $excludeEntryId = null): float;

    public function duplicateEntryExists(
        string $userId,
        Carbon $date,
        ?string $projectId,
        ?string $costCodeId,
        ?string $excludeEntryId = null,
    ): bool;

    public function aggregateHoursByUserAndDate(
        string $userId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId,
        bool $groupByProject,
    ): Collection;
}
