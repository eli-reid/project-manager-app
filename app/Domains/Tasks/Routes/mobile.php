<?php

use App\Domains\Tasks\Livewire\User\Tasks\Index as UserTaskIndex;
use App\Domains\Tasks\Models\Task;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks')
    ->name('tasks.')
    ->group(function (): void {
        Route::get('/', UserTaskIndex::class)
            ->middleware('can:viewAny,'.Task::class)
            ->name('index');
    });
