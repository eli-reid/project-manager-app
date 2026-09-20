<?php

declare(strict_types=1);

use App\Domains\Plans\Services\PlanTitleBlockImageCropper;

it('converts normalized title block coordinates to bounded pixels', function (): void {
    $method = new ReflectionMethod(PlanTitleBlockImageCropper::class, 'pixelRegion');
    $method->setAccessible(true);

    $region = $method->invoke(new PlanTitleBlockImageCropper, [
        'x' => 0.78,
        'y' => 0.55,
        'width' => 0.22,
        'height' => 0.45,
    ], 1000, 2000);

    expect($region)->toBe([
        'x' => 780,
        'y' => 1100,
        'width' => 220,
        'height' => 900,
    ]);
});

it('keeps title block crop dimensions positive at page edges', function (): void {
    $method = new ReflectionMethod(PlanTitleBlockImageCropper::class, 'pixelRegion');
    $method->setAccessible(true);

    $region = $method->invoke(new PlanTitleBlockImageCropper, [
        'x' => 1.0,
        'y' => 1.0,
        'width' => 0.22,
        'height' => 0.45,
    ], 1000, 2000);

    expect($region)->toBe([
        'x' => 999,
        'y' => 1999,
        'width' => 1,
        'height' => 1,
    ]);
});
