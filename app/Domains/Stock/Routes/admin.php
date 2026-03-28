<?php

use App\Domains\Stock\Livewire\Admin\StockOrders\Index as StockOrdersIndex;
use App\Domains\Stock\Livewire\Admin\StockOrders\Show as StockOrdersShow;
use App\Domains\Stock\Livewire\Admin\Templates\Form as TemplatesForm;
use App\Domains\Stock\Livewire\Admin\Templates\Index as TemplatesIndex;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Support\Facades\Route;

Route::prefix('stock-orders')
    ->name('stock-orders.')
    ->middleware('can:viewAny,'.StockOrder::class)
    ->group(function (): void {
        Route::get('/', StockOrdersIndex::class)->name('index');
        Route::get('/{stockOrder}', StockOrdersShow::class)->name('show');
    });

Route::prefix('stock-order-templates')
    ->name('stock-order-templates.')
    ->middleware('can:viewAny,'.StockOrderTemplate::class)
    ->group(function (): void {
        Route::get('/', TemplatesIndex::class)->name('index');
        Route::get('/create', TemplatesForm::class)->name('create');
        Route::get('/{stockOrderTemplate}/edit', TemplatesForm::class)->name('edit');
    });
