<?php

use App\Domains\Payroll\Livewire\User\PayrollHistory\Index as PayrollHistoryIndex;
use App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll\Index as CertifiedPayrollIndex;
use App\Domains\Payroll\Livewire\User\Reports\LaborCost\Index as PayrollLaborCostIndex;
use App\Domains\Payroll\Livewire\User\Reports\TaxFilings\Index as PayrollTaxFilingsIndex;
use App\Domains\Payroll\Livewire\User\Reports\UnionRemittance\Index as PayrollUnionRemittanceIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::get('/payroll/certified-wh347', CertifiedPayrollIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('payroll.certified.index');

        Route::get('/payroll/tax-filings', PayrollTaxFilingsIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('payroll.tax-filings.index');

        Route::get('/payroll/labor-cost', PayrollLaborCostIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('payroll.labor-cost.index');

        Route::get('/payroll/union-remittance', PayrollUnionRemittanceIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('payroll.union-remittance.index');
    });

Route::prefix('payroll')
    ->name('payroll.')
    ->group(function (): void {
        Route::get('/history', PayrollHistoryIndex::class)
            ->middleware('can:payroll.view')
            ->name('history');
    });
