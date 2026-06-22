<?php

namespace App\Domains\Payroll\Livewire\User\Reports\UnionRemittance;

use App\Domains\Payroll\Services\PayrollReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Union Remittance')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $unionCode = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
        $this->fromDate = now()->subMonthsNoOverflow(1)->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.payroll.export');

        $rows = $this->rows();
        $from = $this->fromDate ?? now()->subMonthsNoOverflow(1)->startOfMonth()->toDateString();
        $to = $this->toDate ?? now()->toDateString();
        $segment = $this->unionCode !== '' ? $this->unionCode : 'all-unions';

        $fileName = 'union-remittance-'.Str::slug($segment.'-'.$from.'-to-'.$to).'.csv';

        return response()->streamDownload(function () use ($rows, $from, $to): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Union Remittance']);
            fputcsv($handle, ['From', $from]);
            fputcsv($handle, ['To', $to]);
            fputcsv($handle, []);
            fputcsv($handle, ['Pay Run End', 'Employee', 'Union Code', 'Deduction', 'Hours', 'Gross Pay', 'Remittance Due']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['pay_run'],
                    $row['employee'],
                    $row['union_code'],
                    $row['deduction'],
                    number_format($row['total_hours'], 2, '.', ''),
                    number_format($row['gross_pay'], 2, '.', ''),
                    number_format($row['remittance_due'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.union-remittance.index', [
            'rows' => $this->rows(),
            'unionCodes' => $this->reportingService()->availableUnionCodes(),
            'totals' => [
                'gross_pay' => (float) collect($this->rows())->sum('gross_pay'),
                'remittance_due' => (float) collect($this->rows())->sum('remittance_due'),
            ],
        ]);
    }

    /**
     * @return array<int, array{pay_run:string,employee:string,union_code:string,deduction:string,total_hours:float,gross_pay:float,remittance_due:float}>
     */
    private function rows(): array
    {
        return $this->reportingService()->unionRemittanceRows(
            $this->unionCode !== '' ? $this->unionCode : null,
            $this->fromDate,
            $this->toDate,
        );
    }

    private function reportingService(): PayrollReportingService
    {
        return app(PayrollReportingService::class);
    }
}
