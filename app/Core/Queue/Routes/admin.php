<?php

use App\Core\Queue\Livewire\Admin\Queue\Dashboard;
use Illuminate\Support\Facades\Route;

Route::prefix('queue')
    ->name('queue.')
    ->middleware('can:queue.viewAny')
    ->group(function (): void {
        Route::get('/', Dashboard::class)->name('index');
    });
