<?php

use App\Domains\Plans\Http\Controllers\PlanImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('plans')->name('plans.')->group(function (): void {
    Route::get('/revisions/{revision}/{variant}', PlanImageController::class)
        ->whereIn('variant', ['thumb', 'preview'])
        ->name('images');
});
