<?php

use App\Domains\Reports\Livewire\User\OperationalReports\Index as OperationalReportsIndex;
use App\Domains\Tasks\Livewire\User\Tasks\Index as UserTaskIndex;
use App\Domains\Tasks\Models\Task;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks')
    ->name('tasks.')
    ->group(function (): void {
        Route::livewire('/', UserTaskIndex::class)
            ->middleware('can:viewAny,'.Task::class)
            ->name('index');
    });

Route::prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::livewire('/operational', OperationalReportsIndex::class)
            ->middleware('can:reports.operational.view')
            ->name('operational.index');
    });
