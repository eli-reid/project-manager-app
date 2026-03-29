<?php

use App\Domains\Documents\Livewire\User\Documents\GlobalIndex;
use App\Domains\Documents\Livewire\User\Documents\Index;
use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')
    ->name('documents.')
    ->group(function (): void {
        Route::get('/', Index::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('index');

        Route::get('/global', GlobalIndex::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('global');
    });
