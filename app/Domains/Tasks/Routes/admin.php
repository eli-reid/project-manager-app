<?php

use App\Domains\Tasks\Livewire\Admin\TaskCategories\Form as TaskCategoryForm;
use App\Domains\Tasks\Livewire\Admin\TaskCategories\Index as TaskCategoryIndex;
use App\Domains\Tasks\Livewire\Admin\Tasks\Form as TaskForm;
use App\Domains\Tasks\Livewire\Admin\Tasks\Index as TaskIndex;
use App\Domains\Tasks\Livewire\Admin\TaskTemplates\Form as TaskTemplateForm;
use App\Domains\Tasks\Livewire\Admin\TaskTemplates\Index as TaskTemplateIndex;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks')
    ->name('tasks.')
    ->middleware('can:viewAny,'.Task::class)
    ->group(function (): void {
        Route::livewire('/', TaskIndex::class)->name('index');

        Route::livewire('/create', TaskForm::class)
            ->middleware('can:create,'.Task::class)
            ->name('create');

        Route::livewire('/{task}/edit', TaskForm::class)
            ->middleware('can:update,task')
            ->name('edit');
    });

Route::prefix('task-categories')
    ->name('task-categories.')
    ->middleware('can:viewAny,'.TaskCategory::class)
    ->group(function (): void {
        Route::livewire('/', TaskCategoryIndex::class)->name('index');

        Route::livewire('/create', TaskCategoryForm::class)
            ->middleware('can:create,'.TaskCategory::class)
            ->name('create');

        Route::livewire('/{taskCategory}/edit', TaskCategoryForm::class)
            ->middleware('can:update,taskCategory')
            ->name('edit');
    });

Route::prefix('task-templates')
    ->name('task-templates.')
    ->middleware('can:viewAny,'.TaskTemplate::class)
    ->group(function (): void {
        Route::livewire('/', TaskTemplateIndex::class)->name('index');

        Route::livewire('/create', TaskTemplateForm::class)
            ->middleware('can:create,'.TaskTemplate::class)
            ->name('create');

        Route::livewire('/{taskTemplate}/edit', TaskTemplateForm::class)
            ->middleware('can:update,taskTemplate')
            ->name('edit');
    });
