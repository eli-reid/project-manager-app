<?php

use App\Domains\Projects\Livewire\Admin\Projects\Form as ProjectForm;
use App\Domains\Projects\Livewire\Admin\Projects\Index as ProjectIndex;
use App\Domains\Projects\Livewire\Admin\Projects\Show as ProjectShow;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')
    ->name('projects.')
    ->middleware('can:viewAny,'.Project::class)
    ->group(function (): void {
        Route::livewire('/', ProjectIndex::class)->name('index');

        Route::livewire('/create', ProjectForm::class)
            ->middleware('can:create,'.Project::class)
            ->name('create');

        Route::livewire('/{project}', ProjectShow::class)
            ->middleware('can:view,project')
            ->name('show');

        Route::livewire('/{project}/edit', ProjectForm::class)
            ->middleware('can:update,project')
            ->name('edit');
    });
