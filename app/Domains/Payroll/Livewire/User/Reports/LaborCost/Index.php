<?php

namespace App\Domains\Payroll\Livewire\User\Reports\LaborCost;

use App\Domains\Payroll\Services\PayrollReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Payroll Labor Cost by Project and Cost Code')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.payroll.export');

        $rows = $this->rows();
        $from = $this->fromDate ?? now()->startOfMonth()->toDateString();
        $to = $this->toDate ?? now()->toDateString();

        $fileName = 'payroll-labor-cost-'.Str::slug($from.'-to-'.$to).'.csv';

        return response()->streamDownload(function () use ($rows, $from, $to): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Payroll Labor Cost by Project, Cost Code, and Employee']);
            fputcsv($handle, ['From', $from]);
            fputcsv($handle, ['To', $to]);
            fputcsv($handle, []);
            fputcsv($handle, ['Project', 'Cost Code', 'Employee', 'Hours', 'Estimated Labor Cost']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['project'],
                    $row['cost_code'],
                    $row['employee'],
                    number_format($row['total_hours'], 2, '.', ''),
                    number_format($row['estimated_labor_cost'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.labor-cost.index', [
            'projects' => $this->reportingService()->activeProjects(),
            'rows' => $this->rows(),
            'totals' => [
                'hours' => (float) collect($this->rows())->sum('total_hours'),
                'cost' => (float) collect($this->rows())->sum('estimated_labor_cost'),
            ],
        ]);
    }

    /**
     * @return array<int, array{project:string,cost_code:string,employee:string,total_hours:float,estimated_labor_cost:float}>
     */
    private function rows(): array
    {
        return $this->reportingService()->laborCostRows(
            $this->projectId !== '' ? $this->projectId : null,
            $this->fromDate,
            $this->toDate,
        );
    }

    private function reportingService(): PayrollReportingService
    {
        return app(PayrollReportingService::class);
    }
}
