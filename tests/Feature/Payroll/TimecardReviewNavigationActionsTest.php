<?php

use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Livewire\Admin\Timecards\Review;

beforeEach(function () {
    Settings::set('app.week_start_day', 'monday');
});

it('moves the timecard review week backward by one week', function () {
    $component = app(Review::class);
    $component->weekStarting = '2026-05-11';

    $component->previousWeek();

    expect($component->weekStarting)->toBe('2026-05-04');
});

it('moves the timecard review week forward by one week', function () {
    $component = app(Review::class);
    $component->weekStarting = '2026-05-11';

    $component->nextWeek();

    expect($component->weekStarting)->toBe('2026-05-18');
});

it('normalizes manual week selection to the configured period start', function () {
    $component = app(Review::class);

    $component->updatedWeekStarting('2026-05-13');

    expect($component->weekStarting)->toBe('2026-05-11');
});
