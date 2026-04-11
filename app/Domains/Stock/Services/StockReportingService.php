<?php

namespace App\Domains\Stock\Services;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Database\Eloquent\Builder;

class StockReportingService
{
    /**
     * @return array{count:int,total:float}
     */
    public function projectMetrics(string $projectId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = $this->baseQuery($projectId, $fromDate, $toDate);

        return [
            'count' => (int) $query->count(),
            'total' => (float) ($query->sum('total_cost') ?? 0.0),
        ];
    }

    public function totalBetween(?string $projectId = null, ?string $fromDate = null, ?string $toDate = null): float
    {
        return (float) ($this->baseQuery($projectId, $fromDate, $toDate)->sum('total_cost') ?? 0.0);
    }

    private function baseQuery(?string $projectId, ?string $fromDate, ?string $toDate): Builder
    {
        $query = StockOrder::query();

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        if ($fromDate !== null) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }
}
