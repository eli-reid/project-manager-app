<?php

use App\Domains\ChangeOrders\Livewire\User\ChangeOrders\Form as ChangeOrderForm;
use App\Domains\ChangeOrders\Livewire\User\ChangeOrders\Index as ChangeOrderIndex;
use App\Domains\ChangeOrders\Livewire\User\ChangeOrders\Show as ChangeOrderShow;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('change-orders')
    ->name('change-orders.mobile.')
    ->group(function (): void {
        Route::livewire('/', ChangeOrderIndex::class)
            ->middleware('can:viewAny,'.ChangeOrder::class)
            ->name('index');

        Route::livewire('/create', ChangeOrderForm::class)
            ->middleware('can:create,'.ChangeOrder::class)
            ->name('create');

        Route::livewire('/{changeOrder}', ChangeOrderShow::class)
            ->middleware('can:view,changeOrder')
            ->name('show');

        Route::livewire('/{changeOrder}/edit', ChangeOrderForm::class)
            ->middleware('can:update,changeOrder')
            ->name('edit');
    });
