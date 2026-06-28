<?php

use App\PlugIns\Cpanel\Http\Controllers\WebmailController;
use Illuminate\Support\Facades\Route;

Route::get('/webmail', [WebmailController::class, 'redirect'])->name('webmail.redirect');
Route::get('/webmail/session', [WebmailController::class, 'session'])->name('webmail.session');
