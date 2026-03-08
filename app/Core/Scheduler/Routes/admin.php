<?php

use App\Core\Scheduler\Livewire\Admin\Tasks\Form as TaskForm;
use App\Core\Scheduler\Livewire\Admin\Tasks\Index as TaskIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('scheduler')->name('scheduler.')->group(function (): void {
    Route::prefix('tasks')->name('tasks.')->group(function (): void {
        Route::get('/', TaskIndex::class)->name('index');
        Route::get('/create', TaskForm::class)->name('create');
        Route::get('/{scheduledTask}/edit', TaskForm::class)->name('edit');
    });
});
