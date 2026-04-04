<?php

namespace App\Domains\Reports\Livewire\User\FinancialReports;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Timecards\Models\TimecardEntry;
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

        if (! Project::query()->whereKey($this->projectId)->exists()) {
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

        $selectedProject = Project::query()
            ->select(['id', 'name', 'project_number'])
            ->find($this->projectId);

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
        $projects = Project::query()
            ->select(['id', 'name', 'project_number'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $projectReport = null;
        $selectedProject = null;

        if ($this->projectId !== '') {
            $selectedProject = Project::query()
                ->select(['id', 'name', 'project_number', 'status'])
                ->find($this->projectId);

            if ($selectedProject !== null) {
                $projectReport = $this->buildProjectReport($selectedProject->id);
            }
        }

        return view('reports::livewire.user.financial-reports.index', [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'projectReport' => $projectReport,
            'phaseOneReports' => [
                [
                    'key' => 'project-profitability',
                    'label' => 'Project Profitability',
                    'description' => 'Analyze revenue, labor, and material totals by project.',
                ],
                [
                    'key' => 'monthly-performance',
                    'label' => 'Monthly Financial Performance',
                    'description' => 'Track month-over-month financial performance trends.',
                ],
                [
                    'key' => 'labor-cost-analysis',
                    'label' => 'Labor Cost Analysis',
                    'description' => 'Review labor cost distribution by project and period.',
                ],
                [
                    'key' => 'material-cost-analysis',
                    'label' => 'Material Cost Analysis',
                    'description' => 'Review material and vendor cost distribution by project and period.',
                ],
            ],
        ]);
    }

    /**
     * @return array{timecard_hours:float,daily_reports_count:int,stock_orders_count:int,total_invoice_amount:float,average_invoice_amount:float}
     */
    private function buildProjectReport(string $projectId): array
    {
        [$fromDate, $toDate] = $this->normalizedDateRange();

        $timecardHoursQuery = TimecardEntry::query()->where('project_id', $projectId);
        $dailyReportsQuery = DailyReport::query()->where('project_id', $projectId);
        $stockOrdersQuery = StockOrder::query()->where('project_id', $projectId);
        $invoicesQuery = Invoice::query()->where('project_id', $projectId);

        if ($fromDate !== null) {
            $timecardHoursQuery->whereDate('date', '>=', $fromDate);
            $dailyReportsQuery->whereDate('report_date', '>=', $fromDate);
            $stockOrdersQuery->whereDate('created_at', '>=', $fromDate);
            $invoicesQuery->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $timecardHoursQuery->whereDate('date', '<=', $toDate);
            $dailyReportsQuery->whereDate('report_date', '<=', $toDate);
            $stockOrdersQuery->whereDate('created_at', '<=', $toDate);
            $invoicesQuery->whereDate('invoice_date', '<=', $toDate);
        }

        $totalInvoiceAmount = (float) ($invoicesQuery->sum('total_amount') ?? 0.0);
        $invoiceCount = (int) $invoicesQuery->count();

        return [
            'timecard_hours' => (float) ($timecardHoursQuery->sum('hours') ?? 0.0),
            'daily_reports_count' => (int) $dailyReportsQuery->count(),
            'stock_orders_count' => (int) $stockOrdersQuery->count(),
            'total_invoice_amount' => $totalInvoiceAmount,
            'average_invoice_amount' => $invoiceCount > 0 ? $totalInvoiceAmount / $invoiceCount : 0.0,
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
