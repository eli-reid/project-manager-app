<?php

use App\Domains\RFIs\Livewire\Admin\RFIs\Index as RFIsIndex;
use App\Domains\RFIs\Livewire\Admin\RFIs\Show as RFIsShow;
use App\Domains\RFIs\Models\RFI;
use Illuminate\Support\Facades\Route;

Route::prefix('rfis')
    ->name('rfis.')
    ->middleware('can:viewAny,'.RFI::class)
    ->group(function (): void {
        Route::livewire('/', RFIsIndex::class)->name('index');
        Route::livewire('/{rfi}', RFIsShow::class)->name('show');
    });
