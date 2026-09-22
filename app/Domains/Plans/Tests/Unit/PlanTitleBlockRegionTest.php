<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\PlanTitleBlockRegion;

it('keeps the legacy right side preset focused on the bottom-right title and number', function (): void {
    Settings::set('plans.title_block_region', 'right-strip');

    expect((new PlanTitleBlockRegion)->resolve())->toBe([
        'x' => 0.84,
        'y' => 0.80,
        'width' => 0.16,
        'height' => 0.20,
    ]);
});

it('allows full page OCR when no region should be cropped', function (): void {
    Settings::set('plans.title_block_region', 'full-page');

    expect((new PlanTitleBlockRegion)->resolve())->toBeNull();
});

it('resolves the bottom right title block preset', function (): void {
    Settings::set('plans.title_block_region', 'bottom-right');

    expect((new PlanTitleBlockRegion)->resolve())->toBe([
        'x' => 0.84,
        'y' => 0.80,
        'width' => 0.16,
        'height' => 0.20,
    ]);
});
