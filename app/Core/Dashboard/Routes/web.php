<?php

use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard::index')->name('dashboard');
Route::view('mobile/dashboard', 'dashboard::mobile.index')->name('mobile.dashboard');
