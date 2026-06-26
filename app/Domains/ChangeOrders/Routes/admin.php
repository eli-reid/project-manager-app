<?php

use App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders\Form as ChangeOrderForm;
use App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders\Index as ChangeOrderIndex;
use App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders\Show as ChangeOrderShow;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('change-orders')
    ->name('change-orders.')
    ->middleware('can:viewAny,'.ChangeOrder::class)
    ->group(function (): void {
        Route::livewire('/', ChangeOrderIndex::class)->name('index');

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
