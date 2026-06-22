<?php

namespace App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll;

use App\Domains\Payroll\Services\PayrollReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Certified Payroll (WH-347)')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public string $weekStarting = '';

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
        $this->weekStarting = now()->startOfWeek()->toDateString();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.payroll.export');

        $weekStart = Carbon::parse($this->weekStarting)->startOfWeek();
        $rows = $this->reportingService()->certifiedPayrollRows(
            $this->projectId !== '' ? $this->projectId : null,
            $weekStart,
        );

        $fileName = 'certified-payroll-wh347-'.Str::slug($weekStart->toDateString()).'.csv';

        return response()->streamDownload(function () use ($rows, $weekStart): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Certified Payroll (WH-347)']);
            fputcsv($handle, ['Week Starting', $weekStart->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, ['Employee', 'Project', 'Cost Code', 'Classification', 'Regular Hours', 'OT Hours', 'DT Hours', 'Total Hours', 'Base Rate', 'Fringe Rate', 'Gross Wages', 'Fringe Due']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['employee'],
                    $row['project'],
                    $row['cost_code'],
                    $row['classification'],
                    number_format($row['regular_hours'], 2, '.', ''),
                    number_format($row['overtime_hours'], 2, '.', ''),
                    number_format($row['double_time_hours'], 2, '.', ''),
                    number_format($row['total_hours'], 2, '.', ''),
                    number_format($row['base_rate'], 4, '.', ''),
                    number_format($row['fringe_rate'], 4, '.', ''),
                    number_format($row['gross_wages'], 2, '.', ''),
                    number_format($row['fringe_due'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.certified-payroll.index', [
            'projects' => $this->reportingService()->activeProjects(),
            'rows' => $this->rows(),
            'totals' => $this->totals(),
        ]);
    }

    /**
     * @return array<int, array{employee:string,project:string,cost_code:string,classification:string,regular_hours:float,overtime_hours:float,double_time_hours:float,total_hours:float,base_rate:float,fringe_rate:float,gross_wages:float,fringe_due:float}>
     */
    private function rows(): array
    {
        return $this->reportingService()->certifiedPayrollRows(
            $this->projectId !== '' ? $this->projectId : null,
            Carbon::parse($this->weekStarting)->startOfWeek(),
        );
    }

    /**
     * @return array{total_hours:float,total_gross_wages:float,total_fringe_due:float}
     */
    private function totals(): array
    {
        $rows = collect($this->rows());

        return [
            'total_hours' => (float) $rows->sum('total_hours'),
            'total_gross_wages' => (float) $rows->sum('gross_wages'),
            'total_fringe_due' => (float) $rows->sum('fringe_due'),
        ];
    }

    private function reportingService(): PayrollReportingService
    {
        return app(PayrollReportingService::class);
    }
}
