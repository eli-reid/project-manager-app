<?php

use App\Core\Announcement\Livewire\User\Announcements\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('/mobile/announcements', Index::class)
    ->name('mobile.announcements.index');
