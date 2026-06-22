<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TimecardReportingService
{
    public function totalProjectHours(string $projectId, ?string $fromDate = null, ?string $toDate = null): float
    {
        return (float) ($this->baseQuery($projectId, $fromDate, $toDate)->sum('hours') ?? 0.0);
    }

    public function totalHoursBetween(string $fromDate, string $toDate): float
    {
        return (float) ($this->baseQuery(null, $fromDate, $toDate)->sum('hours') ?? 0.0);
    }

    /**
     * @return Collection<int, TimecardEntry>
     */
    public function laborCostEntries(?string $projectId = null, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = TimecardEntry::query()
            ->whereNotNull('project_id')
            ->whereNull('leave_type')
            ->with(['user:id,first_name,last_name,username', 'project:id,name,project_number']);

        if ($fromDate !== null) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('date', '<=', $toDate);
        }

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }

    private function baseQuery(?string $projectId, ?string $fromDate, ?string $toDate): Builder
    {
        $query = TimecardEntry::query();

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        if ($fromDate !== null) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('date', '<=', $toDate);
        }

        return $query;
    }
}
