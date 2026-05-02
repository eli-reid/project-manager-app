<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('user::auth.login');
})->name('home');

if (app()->environment('local')) {
}

require __DIR__.'/settings.php';
