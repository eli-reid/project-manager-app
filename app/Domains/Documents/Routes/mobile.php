<?php

use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/documents')
    ->name('documents.mobile.')
    ->group(function (): void {
        Route::get('/', fn () => response('Documents Mobile (Scaffold)'))
            ->middleware('can:viewAny,'.Document::class)
            ->name('index');
    });
