<?php

use App\Domains\Submittals\Livewire\Admin\Submittals\Index as SubmittalsIndex;
use App\Domains\Submittals\Livewire\Admin\Submittals\Show as SubmittalsShow;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Support\Facades\Route;

Route::prefix('submittals')
    ->name('submittals.')
    ->middleware('can:viewAny,'.Submittal::class)
    ->group(function (): void {
        Route::livewire('/', SubmittalsIndex::class)->name('index');
        Route::livewire('/{submittal}', SubmittalsShow::class)->name('show');
    });
