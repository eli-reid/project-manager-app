<?php

namespace App\Domains\Projects\Services;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Payroll\Services\PayrollReportingService;
use App\Domains\Projects\Models\Project;

class ProjectFinancialsService
{
    public function __construct(
        private readonly PayrollReportingService $payrollReportingService,
    ) {}

    /**
     * @return array{
     *     budget: float|null,
     *     invoiced: float,
     *     labor_cost: float,
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

        $laborCost = $this->payrollReportingService->estimatedLaborCostTotalForProject((string) $project->id);

        $remaining = $budget !== null ? $budget - $invoiced : null;

        $variancePct = ($budget !== null && $budget > 0)
            ? round(($invoiced / $budget) * 100, 1)
            : null;

        return [
            'budget' => $budget,
            'invoiced' => $invoiced,
            'labor_cost' => $laborCost,
            'remaining' => $remaining,
            'variance_pct' => $variancePct,
            'invoice_count' => $invoiceCount,
        ];
    }
}
