<?php

use App\Domains\Dailies\Livewire\User\Dailies\Form;
use App\Domains\Dailies\Livewire\User\Dailies\Index;
use App\Domains\Dailies\Livewire\User\Dailies\Show;
use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Support\Facades\Route;

Route::prefix('dailies')
    ->name('dailies.')
    ->group(function (): void {
        Route::get('/', Index::class)
            ->middleware('can:viewAny,'.DailyReport::class)
            ->name('index');

        Route::get('/create', Form::class)
            ->middleware('can:create,'.DailyReport::class)
            ->name('create');

        Route::get('/{dailyReport}', Show::class)
            ->middleware('can:view,dailyReport')
            ->name('show');

        Route::get('/{dailyReport}/edit', Form::class)
            ->middleware('can:update,dailyReport')
            ->name('edit');
    });
