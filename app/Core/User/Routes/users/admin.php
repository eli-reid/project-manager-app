<?php

use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', UserIndex::class)->name('index');
    Route::get('/create', UserForm::class)->name('create');
    Route::get('/{user}/edit', UserForm::class)->name('edit');
});
