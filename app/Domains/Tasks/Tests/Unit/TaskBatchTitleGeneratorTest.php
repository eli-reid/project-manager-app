<?php

use App\Domains\Tasks\Support\TaskBatchTitleGenerator;

it('increments existing numeric suffixes and preserves zero padding', function (): void {
    $titles = app(TaskBatchTitleGenerator::class)->generate('Area 009', 3, 1);

    expect($titles)->toBe(['Area 009', 'Area 010', 'Area 011']);
});

it('appends starting numbers when the title has no digits', function (): void {
    $titles = app(TaskBatchTitleGenerator::class)->generate('Safety Check', 3, 4);

    expect($titles)->toBe(['Safety Check 4', 'Safety Check 5', 'Safety Check 6']);
});

it('handles category-like names with numeric suffixes', function (): void {
    $titles = app(TaskBatchTitleGenerator::class)->generate('Phase 01', 3, 1);

    expect($titles)->toBe(['Phase 01', 'Phase 02', 'Phase 03']);
});

it('handles category-like names without digits', function (): void {
    $titles = app(TaskBatchTitleGenerator::class)->generate('Electrical', 2, 7);

    expect($titles)->toBe(['Electrical 7', 'Electrical 8']);
});
