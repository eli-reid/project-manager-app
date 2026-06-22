<?php

namespace App\Domains\Reports\Livewire\User\MaterialCostAnalysis;

use App\Domains\Invoices\Services\InvoiceReportingService;
use App\Domains\Projects\Services\ProjectReportingService;
use App\Domains\Stock\Services\StockReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Material Cost Analysis')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $drillDown = 'project'; // 'project' | 'month' | 'type'

    public function mount(): void
    {
        $this->authorize('reports.financial.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.financial.export');

        $rows = $this->buildRows();
        [$fromDate, $toDate] = $this->normalizedDateRange();

        $fileName = 'material-cost-analysis-'.Str::slug($fromDate.'-to-'.$toDate).'.csv';

        return response()->streamDownload(function () use ($rows, $fromDate, $toDate): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Material Cost Analysis']);
            fputcsv($handle, ['From', $fromDate ?? '']);
            fputcsv($handle, ['To', $toDate ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Dimension', 'Invoice Total', 'Stock Cost', 'Combined Cost']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    number_format($row['invoice_total'], 2, '.', ''),
                    number_format($row['stock_cost'], 2, '.', ''),
                    number_format($row['combined'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        $projects = app(ProjectReportingService::class)->activeProjects();

        return view('reports::livewire.user.material-cost-analysis.index', [
            'rows' => $this->buildRows(),
            'projects' => $projects,
        ]);
    }

    /**
     * @return array<int, array{label:string,invoice_total:float,stock_cost:float,combined:float}>
     */
    private function buildRows(): array
    {
        [$fromDate, $toDate] = $this->normalizedDateRange();

        if ($this->drillDown === 'month') {
            return $this->groupByMonth($fromDate, $toDate);
        }

        if ($this->drillDown === 'type') {
            return $this->groupByType($fromDate, $toDate);
        }

        return $this->groupByProject($fromDate, $toDate);
    }

    /**
     * @return array<int, array{label:string,invoice_total:float,stock_cost:float,combined:float}>
     */
    private function groupByProject(?string $fromDate, ?string $toDate): array
    {
        $invoiceReportingService = app(InvoiceReportingService::class);
        $projects = app(ProjectReportingService::class)->activeProjects()
            ->when($this->projectId !== '', fn ($collection) => $collection->where('id', $this->projectId));
        $stockReportingService = app(StockReportingService::class);

        $rows = [];

        foreach ($projects as $project) {
            $invoiceTotal = $invoiceReportingService->totalBetween($project->id, $fromDate, $toDate);
            $stockCost = $stockReportingService->totalBetween($project->id, $fromDate, $toDate);

            if ($invoiceTotal === 0.0 && $stockCost === 0.0) {
                continue;
            }

            $rows[] = [
                'label' => $project->project_number
                    ? $project->project_number.' - '.$project->name
                    : $project->name,
                'invoice_total' => round($invoiceTotal, 2),
                'stock_cost' => round($stockCost, 2),
                'combined' => round($invoiceTotal + $stockCost, 2),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['combined'] <=> $a['combined']);

        return $rows;
    }

    /**
     * @return array<int, array{label:string,invoice_total:float,stock_cost:float,combined:float}>
     */
    private function groupByMonth(?string $fromDate, ?string $toDate): array
    {
        $invoiceReportingService = app(InvoiceReportingService::class);
        $start = $fromDate ? date('Y-m-01', strtotime($fromDate)) : date('Y-m-01', strtotime('-11 months'));
        $end = $toDate ?? now()->toDateString();
        $stockReportingService = app(StockReportingService::class);

        $current = $start;
        $rows = [];

        while ($current <= $end) {
            $monthEnd = date('Y-m-t', strtotime($current));
            $invoiceTotal = $invoiceReportingService->totalBetween($this->projectId !== '' ? $this->projectId : null, $current, $monthEnd);
            $stockCost = $stockReportingService->totalBetween($this->projectId !== '' ? $this->projectId : null, $current, $monthEnd);

            $rows[] = [
                'label' => date('F Y', strtotime($current)),
                'invoice_total' => round($invoiceTotal, 2),
                'stock_cost' => round($stockCost, 2),
                'combined' => round($invoiceTotal + $stockCost, 2),
            ];

            $current = date('Y-m-01', strtotime($current.' +1 month'));
        }

        return $rows;
    }

    /**
     * @return array<int, array{label:string,invoice_total:float,stock_cost:float,combined:float}>
     */
    private function groupByType(?string $fromDate, ?string $toDate): array
    {
        $invoiceReportingService = app(InvoiceReportingService::class);
        $stockReportingService = app(StockReportingService::class);
        $projectId = $this->projectId !== '' ? $this->projectId : null;
        $invoiceTotal = $invoiceReportingService->totalBetween($projectId, $fromDate, $toDate);
        $stockCost = $stockReportingService->totalBetween($projectId, $fromDate, $toDate);

        return [
            [
                'label' => 'Vendor Invoices',
                'invoice_total' => round($invoiceTotal, 2),
                'stock_cost' => 0.0,
                'combined' => round($invoiceTotal, 2),
            ],
            [
                'label' => 'Stock Orders',
                'invoice_total' => 0.0,
                'stock_cost' => round($stockCost, 2),
                'combined' => round($stockCost, 2),
            ],
        ];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function normalizedDateRange(): array
    {
        $fromDate = filled($this->fromDate) ? (string) $this->fromDate : null;
        $toDate = filled($this->toDate) ? (string) $this->toDate : null;

        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            return [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }
}
