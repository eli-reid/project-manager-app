<?php

use App\Domains\Projects\Livewire\User\Projects\Index;
use App\Domains\Projects\Livewire\User\Projects\Show;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\Route;

Route::prefix('projects/mobile')
    ->name('projects.mobile.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Project::class)
            ->name('index');

        Route::livewire('/{project}', Show::class)
            ->middleware('can:view,project')
            ->name('show');
    });
