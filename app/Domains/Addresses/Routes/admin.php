<?php

use App\Domains\Addresses\Livewire\Admin\Addresses\Form;
use App\Domains\Addresses\Livewire\Admin\Addresses\Index;
use App\Domains\Addresses\Models\Address;
use Illuminate\Support\Facades\Route;

Route::prefix('addresses')
    ->name('addresses.')
    ->middleware('can:viewAny,'.Address::class)
    ->group(function (): void {
        Route::get('/', Index::class)->name('index');

        Route::get('/create', Form::class)
            ->middleware('can:create,'.Address::class)
            ->name('create');

        Route::get('/{address}/edit', Form::class)
            ->middleware('can:update,address')
            ->name('edit');
    });
