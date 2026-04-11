<?php

namespace App\Domains\Dailies\Services;

use App\Domains\Dailies\Models\DailyReport;

class DailyReportingService
{
    public function countForProject(string $projectId, ?string $fromDate = null, ?string $toDate = null): int
    {
        $query = DailyReport::query()->where('project_id', $projectId);

        if ($fromDate !== null) {
            $query->whereDate('report_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('report_date', '<=', $toDate);
        }

        return (int) $query->count();
    }
}
