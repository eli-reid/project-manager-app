<?php

use App\Domains\Stock\Livewire\Mobile\StockOrders\Form;
use App\Domains\Stock\Livewire\Mobile\StockOrders\Index;
use App\Domains\Stock\Livewire\Mobile\StockOrders\Show;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/stock-orders')
    ->name('stock-orders.mobile.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.StockOrder::class)
            ->name('create');

        Route::livewire('/{stockOrder}', Show::class)
            ->middleware('can:view,stockOrder')
            ->name('show');

        Route::livewire('/{stockOrder}/edit', Form::class)
            ->middleware('can:update,stockOrder')
            ->name('edit');
    });
