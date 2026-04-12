<?php

use App\Domains\Payroll\Livewire\Admin\PayRates\Form as PayRateForm;
use App\Domains\Payroll\Livewire\Admin\PayRates\Index as PayRateIndex;
use App\Domains\Payroll\Livewire\Admin\PayRateTypes\Index as PayRateTypeIndex;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Form as PayRunForm;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Index as PayRunIndex;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Show as PayRunShow;
use App\Domains\Payroll\Livewire\Admin\Timecards\Review as TimecardReview;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll')
    ->name('payroll.')
    ->group(function (): void {
        Route::get('/rate-types', PayRateTypeIndex::class)
            ->middleware('can:payroll-rates.view')
            ->name('rate-types.index');

        Route::get('/rates', PayRateIndex::class)
            ->middleware('can:payroll-rates.view')
            ->name('rates.index');

        Route::get('/rates/create', PayRateForm::class)
            ->middleware('can:payroll-rates.manage')
            ->name('rates.create');

        Route::get('/rates/{payRate}/edit', PayRateForm::class)
            ->middleware('can:payroll-rates.manage')
            ->name('rates.edit');

        Route::get('/timecards/review', TimecardReview::class)
            ->middleware('can:payroll-timecards.view')
            ->name('timecards.review');

        Route::get('/runs', PayRunIndex::class)
            ->middleware('can:payroll-runs.preview')
            ->name('runs.index');

        Route::get('/runs/create', PayRunForm::class)
            ->middleware('can:payroll-runs.preview')
            ->name('runs.create');

        Route::get('/runs/{payRun}', PayRunShow::class)
            ->middleware('can:payroll-runs.preview')
            ->name('runs.show');
    });
