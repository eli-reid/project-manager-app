<?php

use App\Core\Identity\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/settings')
    ->name('settings.mobile.')
    ->middleware(['auth'])
    ->group(function (): void {
        Route::livewire('/profile', Profile::class)
            ->name('profile');
    });
