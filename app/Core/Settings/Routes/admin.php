<?php

use App\Core\Settings\Http\Controllers\SettingsController;
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
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/import', [SettingsController::class, 'import'])
            ->middleware('can:import,'.SettingsSqlite::class)
            ->name('import');
    });
});
