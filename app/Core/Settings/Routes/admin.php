<?php

use App\Core\Settings\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Routes
 *
 * All routes in this group require authentication and admin authorization.
 * Prefix: /admin
 */
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Settings management routes
    Route::group(['prefix' => 'settings', 'name' => 'settings.'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/export', [SettingsController::class, 'export'])->name('export');
    });
});
