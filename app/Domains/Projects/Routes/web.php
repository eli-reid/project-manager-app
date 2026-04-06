<?php

use App\Domains\Projects\Livewire\User\Projects\Index;
use App\Domains\Projects\Livewire\User\Projects\Show;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')
    ->name('projects.')
    ->group(function (): void {
        Route::get('/', Index::class)
            ->middleware('can:viewAny,'.Project::class)
            ->name('index');

        Route::get('/{project}', Show::class)
            ->middleware('can:view,project')
            ->name('show');
    });
