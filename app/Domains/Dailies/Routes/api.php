<?php

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Support\Facades\Route;

Route::prefix('dailies')
    ->name('dailies.')
    ->group(function (): void {
        Route::get('/', function () {
            return response()->json([
                'message' => 'Dailies API scaffold is ready.',
            ]);
        })
            ->middleware('can:viewAny,'.DailyReport::class)
            ->name('index');
    });
