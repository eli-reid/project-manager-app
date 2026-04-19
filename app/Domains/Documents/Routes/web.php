<?php

use App\Domains\Documents\Livewire\User\Documents\GlobalIndex;
use App\Domains\Documents\Livewire\User\Documents\Index;
use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')
    ->name('documents.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('index');

        Route::livewire('/global', GlobalIndex::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('global');
    });
