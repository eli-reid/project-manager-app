<?php

use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')
    ->name('documents.')
    ->middleware('can:viewAny,'.Document::class)
    ->group(function (): void {
        Route::get('/', function () {
            return response()->json([
                'message' => 'Documents API Scaffold',
            ]);
        })->name('index');
    });
