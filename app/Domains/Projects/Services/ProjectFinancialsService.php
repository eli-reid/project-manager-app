<?php

namespace App\Domains\Projects\Services;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;

class ProjectFinancialsService
{
    /**
     * @return array{
     *     budget: float|null,
     *     invoiced: float,
     *     remaining: float|null,
     *     variance_pct: float|null,
     *     invoice_count: int,
     * }
     */
    public function summary(Project $project): array
    {
        $invoiced = (float) Invoice::query()
            ->where('project_id', $project->id)
            ->sum('total_amount');

        $invoiceCount = Invoice::query()
            ->where('project_id', $project->id)
            ->count();

        $budget = $project->budget !== null ? (float) $project->budget : null;

        $remaining = $budget !== null ? $budget - $invoiced : null;

        $variancePct = ($budget !== null && $budget > 0)
            ? round(($invoiced / $budget) * 100, 1)
            : null;

        return [
            'budget' => $budget,
            'invoiced' => $invoiced,
            'remaining' => $remaining,
            'variance_pct' => $variancePct,
            'invoice_count' => $invoiceCount,
        ];
    }
}
