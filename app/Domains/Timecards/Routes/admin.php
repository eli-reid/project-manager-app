<?php

use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards')
    ->name('timecards.')
    ->middleware('can:viewAny,'.Timecard::class)
    ->group(function (): void {
        Route::get('/', Index::class)->name('index');
    });
