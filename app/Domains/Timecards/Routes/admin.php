<?php

use App\Domains\Timecards\Livewire\Admin\Timecards\Form;
use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Livewire\Admin\Timecards\Show;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards')
    ->name('timecards.')
    ->middleware('can:viewAll,'.Timecard::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::livewire('/{timecard}/edit', Form::class)
            ->middleware('can:update,timecard')
            ->name('edit');

        Route::livewire('/{timecard}', Show::class)
            ->middleware('can:view,timecard')
            ->name('show');
    });
