<?php

use App\Domains\Timecards\Livewire\Admin\Timecards\Form;
use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Livewire\Admin\Timecards\Show;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards')
    ->name('timecards.')
    ->middleware('can:viewAny,'.Timecard::class)
    ->group(function (): void {
        Route::get('/', Index::class)->name('index');

        Route::get('/create', Form::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::get('/{timecard}/edit', Form::class)
            ->middleware('can:update,timecard')
            ->name('edit');

        Route::get('/{timecard}', Show::class)
            ->middleware('can:view,timecard')
            ->name('show');
    });
