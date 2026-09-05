<?php

use App\Core\Assets\Services\DefaultFilePathNormalizer;

beforeEach(function (): void {
    $this->normalizer = new DefaultFilePathNormalizer;
});

it('returns null for empty or non-string input', function (mixed $input): void {
    expect($this->normalizer->normalize($input))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace' => ['   '],
    'integer' => [123],
    'array' => [[['a']]],
    'separators only' => ['///'],
]);

it('collapses duplicate separators', function (): void {
    expect($this->normalizer->normalize('plans//civil///phase1'))->toBe('plans/civil/phase1');
});

it('normalizes windows separators', function (): void {
    expect($this->normalizer->normalize('plans\\civil'))->toBe('plans/civil');
});

it('strips traversal segments', function (): void {
    expect($this->normalizer->normalize('../../etc/passwd'))->toBe('etc/passwd')
        ->and($this->normalizer->normalize('plans/../secrets'))->toBe('plans/secrets');
});

it('preserves meaningful nesting', function (): void {
    expect($this->normalizer->normalize('Project A/Rev 2/drawings'))->toBe('Project A/Rev 2/drawings');
});
