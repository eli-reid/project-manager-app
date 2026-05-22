<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('user::auth.login');
})->name('home');

// Backward-compatible redirects for legacy mobile project URLs.
Route::get('/projects/mobile', function () {
    return redirect()->route('projects.mobile.index');
});

Route::get('/projects/mobile/{project}/{path?}', function (string $project) {
    return redirect()->route('projects.mobile.show', ['project' => $project]);
})->where('path', '.*');

if (app()->environment('local')) {
}
