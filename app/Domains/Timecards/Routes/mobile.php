<?php

use App\Domains\Timecards\Livewire\User\Timecards\Form;
use App\Domains\Timecards\Livewire\User\Timecards\Index;
use App\Domains\Timecards\Livewire\User\Timecards\Show;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards/mobile')
    ->name('timecards.mobile.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Timecard::class)
            ->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::livewire('/{timecard}', Show::class)
            ->middleware('can:view,timecard')
            ->name('show');

        Route::livewire('/{timecard}/edit', Form::class)
            ->middleware('can:update,timecard')
            ->name('edit');
    });
