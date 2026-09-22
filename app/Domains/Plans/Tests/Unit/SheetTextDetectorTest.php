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

it('prefers a title block sheet number over earlier drawing references', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
GENERAL NOTES
36. DETAILS SHOWN ON SHEET H003, H004 AND H005 ARE APPLICABLE TO ALL EQUIPMENT.
37. REFERENCE DRAWING H002 FOR SEQUENCE OF OPERATION.

SHEET NAME
DETAILS
SHEET NUMBER
H006
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('H006');
});

it('detects spaced decimal sheet numbers from title block extraction text', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
GENERAL NOTES
FOR EQUIPMENT SCHEDULES, SEE DRAWING H002
SHEET NAME
LEGEND, NOTES
AND
ABBREVIATIONS
SHEET NUMBER
H 0.01 0
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('H 0.01');
});

it('detects architectural title block sheet numbers with a trailing revision digit', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
PROJECT SPECIFICATIONS
FOR PERMIT
SHEET NAME
SPECIFICATIONS
SHEET NUMBER
A 0.05 1
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('A 0.05');
});

it('combines multiline sheet names and uses the labeled sheet number line', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
SHEET NAME
BUILDING A
COLUMN
SCHEDULE
SHEET NUMBER
S2.00.a
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('S2.00.A')
        ->and($result['title'])->toBe('BUILDING A COLUMN SCHEDULE')
        ->and($result['confidence'])->toBe(0.9);
});

it('drops oversized labeled titles instead of concatenating a drawing index into the title', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
SHEET NAME
LEGEND, NOTES AND ABBREVIATIONS
DETAILS
LEVEL 01 & 02
LEVEL 03 & 04
DRAWING INDEX
ROOF
EXTRA INDEX ENTRY
SHEET NUMBER
A0.00
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('A0.00')
        ->and($result['title'])->toBeNull()
        ->and($result['confidence'])->toBe(0.65);
});

it('ignores ordinary words that look like sheet number prefixes', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $text = <<<'TEXT'
FOR1 PERMIT NOTES
FOR 4 REASONS LISTED BELOW
SHEET NAME
SPECIFICATIONS
SHEET NUMBER
A 0.05
TEXT;

    $result = (new SheetTextDetector)->detect($text);

    expect($result['sheet_number'])->toBe('A 0.05');
});

it('does not return common words as sheet numbers', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $result = (new SheetTextDetector)->detect("FOR1 PERMIT\nSEE2 NOTES");

    expect($result['sheet_number'])->toBeNull()
        ->and($result['confidence'])->toBe(0.0);
});

it('returns zero confidence when no sheet number is found', function (): void {
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    $result = (new SheetTextDetector)->detect('no');

    expect($result['sheet_number'])->toBeNull()
        ->and($result['title'])->toBeNull()
        ->and($result['confidence'])->toBe(0.0);
});
