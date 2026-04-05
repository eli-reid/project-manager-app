<?php

use App\Core\Auth\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\Auth\User\Livewire\Admin\Users\Index as UserIndex;
use App\Core\Cpanel\Http\Controllers\Admin\GenerateCompanyEmailForUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', UserIndex::class)->name('index');
    Route::get('/create', UserForm::class)->name('create');
    Route::get('/{user}/edit', UserForm::class)->name('edit');
    Route::post('/{user}/generate-company-email', GenerateCompanyEmailForUserController::class)
        ->name('generate-company-email');
});
