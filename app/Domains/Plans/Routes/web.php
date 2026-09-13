<?php

use App\Domains\Plans\Http\Controllers\PlanImageController;
use App\Domains\Plans\Livewire\Mobile\Sheets\Index as MobileIndex;
use App\Domains\Plans\Livewire\Mobile\Sheets\Viewer as MobileViewer;
use App\Domains\Plans\Livewire\Sheets\Compare;
use App\Domains\Plans\Livewire\Sheets\Index;
use App\Domains\Plans\Livewire\Sheets\Viewer;
use Illuminate\Support\Facades\Route;

Route::livewire('/projects/{project}/plans', Index::class)->name('plans.sheets.index');
Route::livewire('/projects/{project}/plans/{sheet}', Viewer::class)->name('plans.sheets.show');
Route::livewire('/projects/{project}/plans/{sheet}/compare', Compare::class)->name('plans.sheets.compare');
Route::livewire('/projects/{project}/plans/mobile', MobileIndex::class)->name('plans.mobile.sheets.index');
Route::livewire('/projects/{project}/plans/mobile/{sheet}', MobileViewer::class)->name('plans.mobile.sheets.show');

Route::prefix('plans')->name('plans.')->group(function (): void {
    Route::get('/revisions/{revision}/{variant}', PlanImageController::class)
        ->whereIn('variant', ['thumb', 'preview'])
        ->name('images');
});
