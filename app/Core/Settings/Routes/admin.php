<?php

use App\Core\Settings\Livewire\Admin\Settings\Import;
use App\Core\Settings\Livewire\Admin\Settings\Index;
use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Support\Facades\Route;

/**
 * Admin Routes
 *
 * All routes in this group require authentication and admin authorization.
 * Prefix: /admin
 */
Route::middleware(['web', 'auth', 'can:viewAny,'.SettingsSqlite::class])->prefix('admin')->name('admin.')->group(function () {
    // Settings management routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::livewire('/', Index::class)->name('index');
        Route::livewire('/import', Import::class)
            ->middleware('can:import,'.SettingsSqlite::class)
            ->name('import');
    });
});
