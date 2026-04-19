<?php

use App\Core\Auth\Role\Livewire\Admin\Roles\Form as RoleForm;
use App\Core\Auth\Role\Livewire\Admin\Roles\Index as RoleIndex;
use App\Core\Auth\Role\Livewire\Admin\Roles\Users as RoleUsers;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->group(function (): void {
    Route::livewire('/', RoleIndex::class)->name('index');
    Route::livewire('/create', RoleForm::class)->name('create');
    Route::livewire('/{role}/edit', RoleForm::class)->name('edit');
    Route::livewire('/{role}/users', RoleUsers::class)->name('users');
});
