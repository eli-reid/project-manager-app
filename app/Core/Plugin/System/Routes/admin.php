<?php

use App\Core\PluginSystem\Livewire\Admin\Plugins\Index;
use App\Core\PluginSystem\Models\InstalledPlugin;
use Illuminate\Support\Facades\Route;

Route::prefix('plugins')
    ->name('plugins.')
    ->middleware('can:viewAny,'.InstalledPlugin::class)
    ->group(function (): void {
        Route::livewire('/', Index::class)->name('index');
    });
