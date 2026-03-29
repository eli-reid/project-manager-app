<?php

use App\Domains\Documents\Livewire\Admin\Documents\Index as AdminDocumentsIndex;
use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')
    ->name('documents.')
    ->middleware('can:deleteAny,'.Document::class)
    ->group(function (): void {
        Route::get('/', AdminDocumentsIndex::class)->name('index');
    });
