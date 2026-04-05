<?php

use App\Core\Auth\Role\Livewire\Admin\Roles\Form as RoleForm;
use App\Core\Auth\Role\Livewire\Admin\Roles\Index as RoleIndex;
use App\Core\Auth\Role\Livewire\Admin\Roles\Users as RoleUsers;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->group(function (): void {
    Route::get('/', RoleIndex::class)->name('index');
    Route::get('/create', RoleForm::class)->name('create');
    Route::get('/{role}/edit', RoleForm::class)->name('edit');
    Route::get('/{role}/users', RoleUsers::class)->name('users');
});
