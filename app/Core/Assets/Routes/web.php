<?php

use App\Core\Assets\Http\Controllers\AssetDeliveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('assets')
    ->name('assets.')
    ->group(function (): void {
        Route::get('/{asset}', [AssetDeliveryController::class, 'download'])->name('download');
        Route::get('/{asset}/preview', [AssetDeliveryController::class, 'preview'])->name('preview');
    });
