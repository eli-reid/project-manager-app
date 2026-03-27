<?php

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('stock-orders')
    ->name('stock-orders.')
    ->group(function (): void {
        Route::get('/', fn () => response('Stock Orders (Scaffold)'))
            ->middleware('can:viewAny,'.StockOrder::class)
            ->name('index');
    });
