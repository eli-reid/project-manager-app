<?php

use App\Core\Announcement\Http\Controllers\Api\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::get('/announcements', [AnnouncementController::class, 'index'])
    ->name('api.announcements.index');
