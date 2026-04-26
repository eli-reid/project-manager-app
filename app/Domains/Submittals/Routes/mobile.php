<?php

use App\Domains\Submittals\Livewire\Submittals\Form as SubmittalForm;
use App\Domains\Submittals\Livewire\Submittals\Index as SubmittalIndex;
use App\Domains\Submittals\Livewire\Submittals\Show as SubmittalShow;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Support\Facades\Route;

Route::prefix('submittals/mobile')
    ->name('submittals.mobile.')
    ->group(function (): void {
        Route::livewire('/', SubmittalIndex::class)
            ->middleware('can:viewAny,'.Submittal::class)
            ->name('index');

        Route::livewire('/create', SubmittalForm::class)
            ->middleware('can:create,'.Submittal::class)
            ->name('create');

        Route::livewire('/{submittal}', SubmittalShow::class)
            ->middleware('can:view,submittal')
            ->name('show');

        Route::livewire('/{submittal}/edit', SubmittalForm::class)
            ->middleware('can:update,submittal')
            ->name('edit');
    });
