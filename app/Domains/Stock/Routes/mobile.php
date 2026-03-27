<?php

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/stock-orders')
    ->name('stock-orders.mobile.')
    ->group(function (): void {
        Route::get('/', fn () => response('Stock Orders Mobile (Scaffold)'))
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');
    });
