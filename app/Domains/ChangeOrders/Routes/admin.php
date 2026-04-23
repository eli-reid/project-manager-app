<?php

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('change-orders')
    ->name('change-orders.')
    ->group(function (): void {
        Route::view('/', 'change-orders::placeholder', ['surface' => 'Admin Change Orders'])
            ->middleware('can:viewAny,'.ChangeOrder::class)
            ->name('index');

        Route::view('/create', 'change-orders::placeholder', ['surface' => 'Create Change Order'])
            ->middleware('can:create,'.ChangeOrder::class)
            ->name('create');

        Route::view('/{changeOrder}', 'change-orders::placeholder', ['surface' => 'Change Order Details'])
            ->middleware('can:view,changeOrder')
            ->name('show');

        Route::view('/{changeOrder}/edit', 'change-orders::placeholder', ['surface' => 'Edit Change Order'])
            ->middleware('can:update,changeOrder')
            ->name('edit');
    });
