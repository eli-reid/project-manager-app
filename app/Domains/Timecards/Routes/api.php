<?php

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('timecards')
    ->name('timecards.')
    ->group(function (): void {
        Route::get('/check-existing', function (Request $request, TimecardWeekService $timecardWeekService): JsonResponse {
            $request->validate([
                'week_starting' => ['required', 'date'],
            ]);

            $user = $request->user();
            abort_unless($user !== null, 401);

            return response()->json([
                'exists' => $timecardWeekService->hasExistingTimecardForWeek(
                    (string) $user->id,
                    (string) $request->string('week_starting')
                ),
            ]);
        })
            ->middleware('can:create,'.Timecard::class)
            ->name('check-existing');
    });
