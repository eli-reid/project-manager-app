<?php

use App\Support\Diagnostics\MemoryProbe;
use Illuminate\Support\Collection;

it('captures a memory snapshot with expected fields', function () {
    $snapshot = MemoryProbe::snapshot('test');

    expect($snapshot)
        ->toHaveKeys(['label', 'current_bytes', 'current_mb', 'real_bytes', 'real_mb', 'peak_bytes', 'peak_mb'])
        ->and($snapshot['label'])->toBe('test')
        ->and($snapshot['current_bytes'])->toBeInt()
        ->and($snapshot['real_bytes'])->toBeInt()
        ->and($snapshot['peak_bytes'])->toBeInt();
});

it('calculates deltas from a baseline snapshot', function () {
    $baseline = MemoryProbe::snapshot('before');

    $payload = \array_fill(0, 500, \str_repeat('x', 64));

    $delta = MemoryProbe::delta($baseline, 'after');

    expect($delta)
        ->toHaveKeys(['delta_current_bytes', 'delta_current_mb', 'delta_real_bytes', 'delta_real_mb', 'delta_peak_bytes', 'delta_peak_mb'])
        ->and($delta['label'])->toBe('after')
        ->and($delta['delta_current_bytes'])->toBeGreaterThanOrEqual(0);

    unset($payload);
});

it('inspects payload type, count, and approximate size', function () {
    $collection = new Collection([
        'small' => 'a',
        'large' => \str_repeat('b', 2048),
    ]);

    $inspection = MemoryProbe::inspect($collection, 'settings');

    expect($inspection)
        ->and($inspection['label'])->toBe('settings')
        ->and($inspection['type'])->toBe(Collection::class)
        ->and($inspection['count'])->toBe(2)
        ->and($inspection['approx_bytes'])->toBeGreaterThan(0);
});

it('ranks iterable items by approximate size', function () {
    $items = [
        'small' => 'a',
        'medium' => \str_repeat('b', 128),
        'large' => \str_repeat('c', 2048),
    ];

    $ranked = MemoryProbe::largestItems($items, 2);

    expect($ranked)->toHaveCount(2)
        ->and($ranked[0]['key'])->toBe('large')
        ->and($ranked[0]['approx_bytes'])->toBeGreaterThan($ranked[1]['approx_bytes']);
});
