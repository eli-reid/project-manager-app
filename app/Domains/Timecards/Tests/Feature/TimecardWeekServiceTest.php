<?php

use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Services\TimecardWeekService;

it('normalizes week start to sunday when configured', function (): void {
    Settings::set('app.week_start_day', 'sunday');

    $service = app(TimecardWeekService::class);

    expect($service->normalizeWeekStart('2026-03-30')->toDateString())
        ->toBe('2026-03-29');
});

it('normalizes week start to monday when configured', function (): void {
    Settings::set('app.week_start_day', 'monday');

    $service = app(TimecardWeekService::class);

    expect($service->normalizeWeekStart('2026-03-30')->toDateString())
        ->toBe('2026-03-30');
    expect($service->normalizeWeekStart('2026-03-29')->toDateString())
        ->toBe('2026-03-23');
});
