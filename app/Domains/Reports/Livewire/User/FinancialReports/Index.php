<?php

namespace App\Domains\Reports\Livewire\User\FinancialReports;

use App\Domains\Dailies\Services\DailyReportingService;
use App\Domains\Invoices\Services\InvoiceReportingService;
use App\Domains\Projects\Services\ProjectReportingService;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Stock\Services\StockReportingService;
use App\Domains\Timecards\Services\TimecardReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Financial Reports')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $this->authorize('reports.financial.view');

        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updatedProjectId(): void
    {
        if ($this->projectId === '') {
            return;
        }

        if (! app(ProjectReportingService::class)->exists($this->projectId)) {
            $this->projectId = '';
        }
    }

    public function exportProjectReport(): ?StreamedResponse
    {
        $this->authorize('reports.financial.export');

        if ($this->projectId === '') {
            $this->addError('projectId', 'Select a project before exporting.');

            return null;
        }

        $selectedProject = app(ProjectReportingService::class)->findSummary($this->projectId);

        if ($selectedProject === null) {
            $this->addError('projectId', 'The selected project could not be found.');

            return null;
        }

        [$fromDate, $toDate] = $this->normalizedDateRange();
        $projectReport = $this->buildProjectReport($selectedProject->id);

        $baseProjectIdentifier = (string) ($selectedProject->project_number ?: $selectedProject->name);
        $fileName = 'project-report-'.Str::slug($baseProjectIdentifier).'.csv';

        return response()->streamDownload(function () use ($selectedProject, $projectReport, $fromDate, $toDate): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Project Report']);
            fputcsv($handle, ['Project', (string) ($selectedProject->project_number ?: $selectedProject->name)]);
            fputcsv($handle, ['From Date', $fromDate ?? '']);
            fputcsv($handle, ['To Date', $toDate ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Timecard Hours', number_format($projectReport['timecard_hours'], 2, '.', '')]);
            fputcsv($handle, ['Daily Reports', (string) $projectReport['daily_reports_count']]);
            fputcsv($handle, ['Stock Orders', (string) $projectReport['stock_orders_count']]);
            fputcsv($handle, ['Total Invoice Amount', number_format($projectReport['total_invoice_amount'], 2, '.', '')]);
            fputcsv($handle, ['Average Invoice Amount', number_format($projectReport['average_invoice_amount'], 2, '.', '')]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $projectReportingService = app(ProjectReportingService::class);

        $projects = $projectReportingService->activeProjects();

        $projectReport = null;
        $selectedProject = null;

        if ($this->projectId !== '') {
            $selectedProject = $projectReportingService->findSummary($this->projectId);

            if ($selectedProject !== null) {
                $projectReport = $this->buildProjectReport($selectedProject->id);
            }
        }

        return view('reports::livewire.user.financial-reports.index', [
            'projects' => $projects,
            'reportCards' => app(ReportRegistry::class)->forSection('financial'),
            'selectedProject' => $selectedProject,
            'projectReport' => $projectReport,
        ]);
    }

    /**
     * @return array{timecard_hours:float,daily_reports_count:int,stock_orders_count:int,total_invoice_amount:float,average_invoice_amount:float}
     */
    private function buildProjectReport(string $projectId): array
    {
        [$fromDate, $toDate] = $this->normalizedDateRange();
        $dailyReportingService = app(DailyReportingService::class);
        $invoiceReportingService = app(InvoiceReportingService::class);
        $stockReportingService = app(StockReportingService::class);
        $timecardReportingService = app(TimecardReportingService::class);

        $invoiceMetrics = $invoiceReportingService->projectMetrics($projectId, $fromDate, $toDate);
        $stockMetrics = $stockReportingService->projectMetrics($projectId, $fromDate, $toDate);

        return [
            'timecard_hours' => $timecardReportingService->totalProjectHours($projectId, $fromDate, $toDate),
            'daily_reports_count' => $dailyReportingService->countForProject($projectId, $fromDate, $toDate),
            'stock_orders_count' => $stockMetrics['count'],
            'total_invoice_amount' => $invoiceMetrics['total'],
            'average_invoice_amount' => $invoiceMetrics['average'],
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
