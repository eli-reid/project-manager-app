<?php

use App\Domains\Projects\Livewire\Admin\Projects\Form as ProjectForm;
use App\Domains\Projects\Livewire\Admin\Projects\Index as ProjectIndex;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')
    ->name('projects.')
    ->middleware('can:viewAny,'.Project::class)
    ->group(function (): void {
        Route::get('/', ProjectIndex::class)->name('index');

        Route::get('/create', ProjectForm::class)
            ->middleware('can:create,'.Project::class)
            ->name('create');
    });
