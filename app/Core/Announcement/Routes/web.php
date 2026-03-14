<?php

use App\Core\Announcement\Http\Controllers\AnnouncementFeedController;
use Illuminate\Support\Facades\Route;

Route::get('/announcements', [AnnouncementFeedController::class, 'index'])
    ->name('announcements.index');
