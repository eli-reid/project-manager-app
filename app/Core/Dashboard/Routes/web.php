<?php

use Illuminate\Support\Facades\Route;

Route::livewire('dashboard', 'dashboard::index')->name('dashboard');
Route::livewire('filament-dashboard', 'dashboard::filament-index')->name('filament.dashboard');
