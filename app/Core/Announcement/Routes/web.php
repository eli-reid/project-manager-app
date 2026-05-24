<?php

use App\Core\Announcement\Livewire\User\Announcements\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('/announcements', Index::class)
    ->name('announcements.index');
