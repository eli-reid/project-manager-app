<?php

use App\Core\Identity\Middleware\PreventAuthPageCaching;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('user::auth.login');
})->middleware(PreventAuthPageCaching::class)->name('home');

if (app()->environment('local')) {
}
