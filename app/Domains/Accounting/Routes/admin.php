<?php

use App\Domains\Accounting\Livewire\Admin\AccountingCodes\Form;
use App\Domains\Accounting\Livewire\Admin\AccountingCodes\Index;
use App\Domains\Accounting\Models\AccountingCode;
use Illuminate\Support\Facades\Route;

Route::prefix('accounting-codes')
    ->name('accounting-codes.')
    ->middleware('can:viewAny,'.AccountingCode::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.AccountingCode::class)
            ->name('create');

        Route::livewire('/{accountingCode}/edit', Form::class)
            ->middleware('can:update,accountingCode')
            ->name('edit');
    });
