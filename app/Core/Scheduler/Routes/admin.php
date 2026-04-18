<?php

use App\Core\Scheduler\Livewire\Admin\Settings\SystemTiming;
use App\Core\Scheduler\Livewire\Admin\Tasks\Form as TaskForm;
use App\Core\Scheduler\Livewire\Admin\Tasks\Index as TaskIndex;
use App\Core\Scheduler\Models\ScheduledTask;
use Illuminate\Support\Facades\Route;

Route::prefix('scheduler')
    ->name('scheduler.')
    ->middleware('can:viewAny,'.ScheduledTask::class)
    ->group(function (): void {
        Route::get('/settings', SystemTiming::class)->name('settings.index');

        Route::prefix('tasks')->name('tasks.')->group(function (): void {
            Route::get('/', TaskIndex::class)->name('index');
            Route::get('/create', TaskForm::class)
                ->middleware('can:create,'.ScheduledTask::class)
                ->name('create');

            Route::get('/{scheduledTask}/edit', TaskForm::class)
                ->middleware('can:update,scheduledTask')
                ->name('edit');
        });
    });
