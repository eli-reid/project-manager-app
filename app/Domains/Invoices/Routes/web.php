<?php

use App\Domains\Invoices\Services\InvoiceReportingService;
use App\Domains\Reports\Livewire\User\MonthlyPerformance\Index as MonthlyPerformanceIndex;
use App\Domains\Stock\Services\StockReportingService;
use App\Domains\Timecards\Services\TimecardReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::get('/financial/monthly-performance', MonthlyPerformanceIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('financial.monthly-performance.index');

        Route::get('/financial/monthly-performance/print', function (Request $request): View {
            $user = $request->user();

            abort_unless($user?->isAdmin() || $user?->hasPermission('financial-reports.export'), 403);
            $year = (int) ($request->query('year', now()->year));
            $invoiceReportingService = app(InvoiceReportingService::class);
            $stockReportingService = app(StockReportingService::class);
            $timecardReportingService = app(TimecardReportingService::class);
            $months = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = sprintf('%04d-%02d-01', $year, $month);
                $end = date('Y-m-t', strtotime($start));
                $hours = $timecardReportingService->totalHoursBetween($start, $end);
                $revenue = $invoiceReportingService->totalBetween(null, $start, $end);
                $stockCost = $stockReportingService->totalBetween(null, $start, $end);

                $months[] = [
                    'label' => date('F', strtotime($start)),
                    'hours' => $hours,
                    'revenue' => $revenue,
                    'stock_cost' => $stockCost,
                    'margin' => $revenue - $stockCost,
                ];
            }

            return view('reports::print.monthly-performance', compact('months', 'year'));
        })->middleware('can:reports.financial.export')->name('financial.monthly-performance.print');
    });
