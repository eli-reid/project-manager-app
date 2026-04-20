<?php

use App\Domains\Projects\Livewire\User\Projects\Index;
use App\Domains\Projects\Livewire\User\Projects\Show;
use App\Domains\Projects\Models\Project;
use App\Domains\Reports\Livewire\User\FinancialReports\Index as FinancialReportsIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')
    ->name('projects.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Project::class)
            ->name('index');

        Route::livewire('/{project}', Show::class)
            ->middleware('can:view,project')
            ->name('show');
    });

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::livewire('/financial', FinancialReportsIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('financial.index');
    });
