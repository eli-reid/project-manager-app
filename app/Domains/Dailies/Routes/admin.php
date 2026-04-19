<?php

use App\Domains\Dailies\Livewire\Admin\Dailies\Form;
use App\Domains\Dailies\Livewire\Admin\Dailies\Index;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show;
use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Support\Facades\Route;

Route::prefix('dailies')
    ->name('dailies.')
    ->middleware('can:viewAll,'.DailyReport::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.DailyReport::class)
            ->name('create');

        Route::livewire('/{dailyReport}', Show::class)
            ->middleware('can:view,dailyReport')
            ->name('show');

        Route::livewire('/{dailyReport}/edit', Form::class)
            ->middleware('can:update,dailyReport')
            ->name('edit');
    });
