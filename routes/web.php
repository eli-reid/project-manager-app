<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('user::auth.login');
})->name('home');

if (app()->environment('local')) {
}

require __DIR__.'/settings.php';
