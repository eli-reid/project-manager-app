<?php

use App\Domains\Reports\Livewire\User\FinancialReports\Index;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::get('/financial', Index::class)
            ->middleware('can:reports.financial.view')
            ->name('financial.index');
    });
