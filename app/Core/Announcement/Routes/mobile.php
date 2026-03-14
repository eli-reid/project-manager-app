<?php

use App\Core\Announcement\Http\Controllers\AnnouncementFeedController;
use Illuminate\Support\Facades\Route;

Route::get('/mobile/announcements', [AnnouncementFeedController::class, 'index'])
    ->name('mobile.announcements.index');
