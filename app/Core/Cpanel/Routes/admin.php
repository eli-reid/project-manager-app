<?php

use App\Core\Cpanel\Http\Controllers\Admin\EmailAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('cpanel')->name('cpanel.')->group(function (): void {
    Route::get('email-accounts', [EmailAccountController::class, 'index'])->name('email-accounts.index');
    Route::post('email-accounts', [EmailAccountController::class, 'store'])->name('email-accounts.store');
    Route::delete('email-accounts/{email}', [EmailAccountController::class, 'destroy'])->name('email-accounts.destroy');
    Route::post('email-accounts/{email}/reset-password', [EmailAccountController::class, 'resetPassword'])->name('email-accounts.reset-password');
    Route::post('email-accounts/{email}/suspend', [EmailAccountController::class, 'suspend'])->name('email-accounts.suspend');
    Route::post('email-accounts/{email}/unsuspend', [EmailAccountController::class, 'unsuspend'])->name('email-accounts.unsuspend');
    Route::get('email-accounts/{email}/forwarders', [EmailAccountController::class, 'listForwarders'])->name('email-accounts.forwarders.index');
    Route::post('email-accounts/{email}/forwarders', [EmailAccountController::class, 'addForwarder'])->name('email-accounts.forwarders.store');
    Route::delete('email-accounts/{email}/forwarders', [EmailAccountController::class, 'deleteForwarder'])->name('email-accounts.forwarders.destroy');
});
