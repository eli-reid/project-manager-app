<?php

use App\Domains\Payroll\Livewire\Admin\Timecards\Review;

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
