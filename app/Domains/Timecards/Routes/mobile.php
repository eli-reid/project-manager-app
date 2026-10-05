<?php

use App\Domains\Timecards\Livewire\Mobile\Timecards\Create as MobileCreate;
use App\Domains\Timecards\Livewire\Mobile\Timecards\EntryForm as MobileEntryForm;
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

        Route::livewire('/create', MobileCreate::class)
            ->middleware('can:create,'.Timecard::class)
            ->name('create');

        Route::livewire('/{timecard}', MobileShow::class)
            ->middleware('can:view,timecard')
            ->name('show');

        Route::redirect('/{timecard}/edit', '/timecards/mobile/{timecard}')
            ->name('edit');

        Route::livewire('/{timecard}/entries/create', MobileEntryForm::class)
            ->middleware('can:update,timecard')
            ->name('entries.create');

        Route::livewire('/{timecard}/entries/{entry}/edit', MobileEntryForm::class)
            ->middleware('can:update,timecard')
            ->scopeBindings()
            ->name('entries.edit');
    });
