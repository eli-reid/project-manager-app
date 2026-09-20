<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\SheetTextDetector;

it('detects a sheet number and title with high confidence', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $result = (new SheetTextDetector)->detect("ELECTRICAL RISER DIAGRAM\nSHEET E 2.01");

    expect($result['sheet_number'])->toBe('E 2.01')
        ->and($result['title'])->toBe('ELECTRICAL RISER DIAGRAM')
        ->and($result['confidence'])->toBe(0.9);
});

it('returns zero confidence when no sheet number is found', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $result = (new SheetTextDetector)->detect('no');

    expect($result['sheet_number'])->toBeNull()
        ->and($result['title'])->toBeNull()
        ->and($result['confidence'])->toBe(0.0);
});
