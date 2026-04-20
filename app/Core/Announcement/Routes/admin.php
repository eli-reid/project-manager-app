<?php

use App\Core\Announcement\Livewire\Admin\Announcements\Form;
use App\Core\Announcement\Livewire\Admin\Announcements\Index;
use App\Core\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Route;

Route::prefix('announcements')
    ->name('announcements.')
    ->middleware('can:viewAny,'.Announcement::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');

        Route::livewire('/create', Form::class)
            ->middleware('can:create,'.Announcement::class)
            ->name('create');

        Route::livewire('/{announcement}/edit', Form::class)
            ->middleware('can:update,announcement')
            ->name('edit');
    });
