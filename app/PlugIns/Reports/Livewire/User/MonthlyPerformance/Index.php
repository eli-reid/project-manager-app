<?php

namespace App\Domains\Reports\Livewire\User\MonthlyPerformance;

use App\Domains\Invoices\Services\InvoiceReportingService;
use App\Domains\Stock\Services\StockReportingService;
use App\Domains\Timecards\Services\TimecardReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Monthly Financial Performance')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $year = '';

    public function mount(): void
    {
        $this->authorize('reports.financial.view');
        $this->year = (string) now()->year;
    }

    public function updatedYear(): void
    {
        // Trigger re-render
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.financial.export');

        $rows = $this->buildMonthlyRows();
        $fileName = 'monthly-performance-'.$this->year.'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Monthly Financial Performance Report']);
            fputcsv($handle, ['Year', $this->year]);
            fputcsv($handle, []);
            fputcsv($handle, ['Month', 'Timecard Hours', 'Invoice Revenue', 'Stock Cost', 'Gross Margin']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['month_label'],
                    number_format($row['hours'], 2, '.', ''),
                    number_format($row['revenue'], 2, '.', ''),
                    number_format($row['stock_cost'], 2, '.', ''),
                    number_format($row['margin'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('reports::livewire.user.monthly-performance.index', [
            'months' => $this->buildMonthlyRows(),
            'availableYears' => $this->availableYears(),
        ]);
    }

    /**
     * @return array<int, array{month:int,month_label:string,hours:float,revenue:float,stock_cost:float,margin:float}>
     */
    private function buildMonthlyRows(): array
    {
        $invoiceReportingService = app(InvoiceReportingService::class);
        $stockReportingService = app(StockReportingService::class);
        $timecardReportingService = app(TimecardReportingService::class);
        $year = (int) $this->year;
        $rows = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));
            $hours = $timecardReportingService->totalHoursBetween($start, $end);
            $revenue = $invoiceReportingService->totalBetween(null, $start, $end);
            $stockCost = $stockReportingService->totalBetween(null, $start, $end);

            $rows[] = [
                'month' => $month,
                'month_label' => date('F', strtotime($start)),
                'hours' => $hours,
                'revenue' => $revenue,
                'stock_cost' => $stockCost,
                'margin' => round($revenue - $stockCost, 2),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, int>
     */
    private function availableYears(): array
    {
        $currentYear = now()->year;

        return range($currentYear - 2, $currentYear + 1);
    }
}
