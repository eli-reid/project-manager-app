<?php

use App\Domains\Reports\Livewire\User\MaterialCostAnalysis\Index as MaterialCostAnalysisIndex;
use App\Domains\Stock\Livewire\User\StockOrders\Form as StockOrderForm;
use App\Domains\Stock\Livewire\User\StockOrders\Index as StockOrderIndex;
use App\Domains\Stock\Livewire\User\StockOrders\Show as StockOrderShow;
use App\Domains\Stock\Livewire\User\Templates\Browse as TemplateBrowse;
use App\Domains\Stock\Livewire\User\Templates\FromTemplate;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Support\Facades\Route;

Route::prefix('stock-orders')
    ->name('stock-orders.')
    ->group(function (): void {
        Route::livewire('/', StockOrderIndex::class)
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');

        Route::livewire('/create', StockOrderForm::class)
            ->middleware('can:create,'.StockOrder::class)
            ->name('create');

        Route::prefix('templates')
            ->name('templates.')
            ->group(function (): void {
                Route::livewire('/', TemplateBrowse::class)
                    ->middleware('can:viewAny,'.StockOrderTemplate::class)
                    ->name('browse');

                Route::livewire('/{stockOrderTemplate}/order', FromTemplate::class)
                    ->middleware('can:view,stockOrderTemplate')
                    ->name('from');
            });

        Route::livewire('/{stockOrder}', StockOrderShow::class)
            ->middleware('can:view,stockOrder')
            ->name('show');

        Route::livewire('/{stockOrder}/edit', StockOrderForm::class)
            ->middleware('can:update,stockOrder')
            ->name('edit');
    });

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::livewire('/financial/material-cost-analysis', MaterialCostAnalysisIndex::class)
            ->middleware('can:reports.financial.view')
            ->name('financial.material-cost-analysis.index');
    });
