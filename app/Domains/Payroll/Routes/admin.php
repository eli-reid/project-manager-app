<?php

use App\Domains\Payroll\Livewire\Admin\PayRates\Form as PayRateForm;
use App\Domains\Payroll\Livewire\Admin\PayRates\Index as PayRateIndex;
use App\Domains\Payroll\Livewire\Admin\PayRateTypes\Index as PayRateTypeIndex;
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
    });
