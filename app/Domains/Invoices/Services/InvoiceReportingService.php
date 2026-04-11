<?php

namespace App\Domains\Invoices\Services;

use App\Domains\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

class InvoiceReportingService
{
    /**
     * @return array{count:int,total:float,average:float}
     */
    public function projectMetrics(string $projectId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = $this->baseQuery($projectId, $fromDate, $toDate);
        $total = (float) ($query->sum('total_amount') ?? 0.0);
        $count = (int) $query->count();

        return [
            'count' => $count,
            'total' => $total,
            'average' => $count > 0 ? $total / $count : 0.0,
        ];
    }

    public function totalBetween(?string $projectId = null, ?string $fromDate = null, ?string $toDate = null): float
    {
        return (float) ($this->baseQuery($projectId, $fromDate, $toDate)->sum('total_amount') ?? 0.0);
    }

    private function baseQuery(?string $projectId, ?string $fromDate, ?string $toDate): Builder
    {
        $query = Invoice::query();

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        if ($fromDate !== null) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('invoice_date', '<=', $toDate);
        }

        return $query;
    }
}
