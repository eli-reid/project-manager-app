<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Support\Facades\DB;

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

it('loads existing weeks in one query when building future week options', function (): void {
    Settings::set('app.week_start_day', 'monday');
    $user = User::factory()->create();
    $currentWeekStart = app(TimecardWeekService::class)->currentWeekStart();

    Timecard::factory()->create([
        'user_id' => $user->id,
        'week_starting' => $currentWeekStart,
    ]);

    DB::enableQueryLog();

    $options = app(TimecardWeekService::class)->futureWeekOptions((string) $user->id, includePreviousWeek: true);

    $timecardQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], '"timecards"'));

    expect($timecardQueries)->toHaveCount(1);
    expect($options->pluck('start'))->not->toContain($currentWeekStart->toDateString());
});
