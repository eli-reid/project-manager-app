<?php

use App\Core\Cpanel\Http\Controllers\Admin\EmailAccountController;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Create as EmailAccountsCreate;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Index as EmailAccountsIndex;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Show as EmailAccountsShow;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\Dashboard as EmailManagementDashboard;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\DomainForwarders as EmailManagementDomainForwarders;
use Illuminate\Support\Facades\Route;

Route::prefix('cpanel')->name('cpanel.')->group(function (): void {
    Route::prefix('manage')->name('manage.')->group(function (): void {
        Route::livewire('/', EmailManagementDashboard::class)->name('dashboard');
        Route::livewire('domain-forwarders', EmailManagementDomainForwarders::class)->name('domain-forwarders');
        Route::livewire('email-accounts/create', EmailAccountsCreate::class)->name('email-accounts.create');
        Route::livewire('email-accounts/{cachedEmailAccount}', EmailAccountsShow::class)->name('email-accounts.show');
        Route::livewire('email-accounts', EmailAccountsIndex::class)->name('email-accounts.index');
    });

    Route::prefix('api')->group(function (): void {
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
});
