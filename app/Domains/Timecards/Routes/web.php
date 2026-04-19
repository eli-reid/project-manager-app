<?php

use App\Domains\Reports\Livewire\User\LaborCostAnalysis\Index as LaborCostAnalysisIndex;
use App\Domains\Timecards\Livewire\User\Timecards\Form;
use App\Domains\Timecards\Livewire\User\Timecards\Index;
use App\Domains\Timecards\Livewire\User\Timecards\Show;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards')
    ->name('timecards.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Timecard::class)
            ->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::livewire('/{timecard}', Show::class)
            ->middleware('can:view,timecard')
            ->name('show');

        Route::livewire('/{timecard}/edit', Form::class)
            ->middleware('can:update,timecard')
            ->name('edit');
    });

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::livewire('/financial/labor-cost-analysis', LaborCostAnalysisIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('financial.labor-cost-analysis.index');
    });
