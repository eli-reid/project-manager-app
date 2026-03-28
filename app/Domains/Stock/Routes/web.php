<?php

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
        Route::get('/', StockOrderIndex::class)
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');

        Route::get('/create', StockOrderForm::class)
            ->middleware('can:create,'.StockOrder::class)
            ->name('create');

        Route::get('/{stockOrder}', StockOrderShow::class)
            ->middleware('can:view,stockOrder')
            ->name('show');

        Route::get('/{stockOrder}/edit', StockOrderForm::class)
            ->middleware('can:update,stockOrder')
            ->name('edit');

        Route::prefix('templates')
            ->name('templates.')
            ->group(function (): void {
                Route::get('/', TemplateBrowse::class)
                    ->middleware('can:view,'.StockOrderTemplate::class)
                    ->name('browse');

                Route::get('/{stockOrderTemplate}/order', FromTemplate::class)
                    ->middleware('can:view,stockOrderTemplate')
                    ->name('from');
            });
    });
