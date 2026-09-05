<?php

use App\Domains\RFIs\Livewire\Admin\RFIs\Index as RFIsIndex;
use App\Domains\RFIs\Livewire\Admin\RFIs\Show as RFIsShow;
use App\Domains\RFIs\Models\RFI;
use Illuminate\Support\Facades\Route;

Route::prefix('rfis')
    ->name('rfis.')
    ->group(function (): void {
        Route::livewire('/', RFIsIndex::class)
            ->middleware('can:viewAny,'.RFI::class)
            ->name('index');

        Route::livewire('/{rfi}', RFIsShow::class)
            ->middleware('can:view,rfi')
            ->name('show');
    });
