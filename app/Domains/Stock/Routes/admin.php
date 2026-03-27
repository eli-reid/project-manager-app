<?php

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('stock-orders')
    ->name('stock-orders.')
    ->middleware('can:viewAny,'.StockOrder::class)
    ->group(function (): void {
        Route::get('/', fn () => response('Stock Orders Admin (Scaffold)'))
            ->name('index');
    });
