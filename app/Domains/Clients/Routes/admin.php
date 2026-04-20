<?php

use App\Domains\Clients\Livewire\Admin\Clients\Form;
use App\Domains\Clients\Livewire\Admin\Clients\Index;
use App\Domains\Clients\Models\Client;
use Illuminate\Support\Facades\Route;

Route::prefix('clients')
    ->name('clients.')
    ->middleware('can:viewAny,'.Client::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.Client::class)
            ->name('create');

        Route::livewire('/{client}/edit', Form::class)
            ->middleware('can:update,client')
            ->name('edit');
    });
