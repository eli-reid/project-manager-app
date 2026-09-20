<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\PlanTitleBlockRegion;

it('resolves the right side title block preset', function (): void {
    Settings::set('plans.title_block_region', 'right-strip');

    expect((new PlanTitleBlockRegion)->resolve())->toBe([
        'x' => 0.78,
        'y' => 0.0,
        'width' => 0.22,
        'height' => 1.0,
    ]);
});

it('allows full page OCR when no region should be cropped', function (): void {
    Settings::set('plans.title_block_region', 'full-page');

    expect((new PlanTitleBlockRegion)->resolve())->toBeNull();
});

it('resolves the bottom right title block preset', function (): void {
    Settings::set('plans.title_block_region', 'bottom-right');

    expect((new PlanTitleBlockRegion)->resolve())->toBe([
        'x' => 0.78,
        'y' => 0.55,
        'width' => 0.22,
        'height' => 0.45,
    ]);
});
