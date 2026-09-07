<?php

use App\Domains\Plans\Http\Controllers\PlanImageController;
use App\Domains\Plans\Livewire\Sheets\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('/projects/{project}/plans', Index::class)->name('plans.sheets.index');

Route::prefix('plans')->name('plans.')->group(function (): void {
    Route::get('/revisions/{revision}/{variant}', PlanImageController::class)
        ->whereIn('variant', ['thumb', 'preview'])
        ->name('images');
});
