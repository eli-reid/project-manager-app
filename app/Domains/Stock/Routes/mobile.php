<?php

use App\Domains\Stock\Livewire\Mobile\StockOrders\Index;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/stock-orders')
    ->name('stock-orders.mobile.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');
    });
