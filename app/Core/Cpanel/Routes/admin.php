<?php

use App\Core\Cpanel\Http\Controllers\Admin\EmailAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('cpanel')->name('cpanel.')->group(function (): void {
    Route::get('email-accounts', [EmailAccountController::class, 'index'])->name('email-accounts.index');
    Route::post('email-accounts', [EmailAccountController::class, 'store'])->name('email-accounts.store');
    Route::delete('email-accounts/{email}', [EmailAccountController::class, 'destroy'])->name('email-accounts.destroy');
});
