<?php

use App\Domains\Timecards\Livewire\Mobile\Timecards\Form as MobileForm;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Index as MobileIndex;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Show as MobileShow;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards/mobile')
    ->name('timecards.mobile.')
    ->group(function (): void {
        Route::livewire('/', MobileIndex::class)
            ->middleware('can:viewAny,'.Timecard::class)
            ->name('index');

        Route::livewire('/create', MobileForm::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::livewire('/{timecard}', MobileShow::class)
            ->middleware('can:view,timecard')
            ->name('show');

        Route::livewire('/{timecard}/edit', MobileForm::class)
            ->middleware('can:update,timecard')
            ->name('edit');
    });
