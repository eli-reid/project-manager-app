<?php

declare(strict_types=1);

use App\Domains\Plans\Services\PlanTitleBlockImageCropper;

it('converts normalized title block coordinates to bounded pixels', function (): void {
    $method = new ReflectionMethod(PlanTitleBlockImageCropper::class, 'pixelRegion');
    $method->setAccessible(true);

    $region = $method->invoke(new PlanTitleBlockImageCropper, [
        'x' => 0.84,
        'y' => 0.80,
        'width' => 0.16,
        'height' => 0.20,
    ], 1000, 2000);

    expect($region)->toBe([
        'x' => 840,
        'y' => 1600,
        'width' => 160,
        'height' => 400,
    ]);
});

it('keeps title block crop dimensions positive at page edges', function (): void {
    $method = new ReflectionMethod(PlanTitleBlockImageCropper::class, 'pixelRegion');
    $method->setAccessible(true);

    $region = $method->invoke(new PlanTitleBlockImageCropper, [
        'x' => 1.0,
        'y' => 1.0,
        'width' => 0.16,
        'height' => 0.20,
    ], 1000, 2000);

    expect($region)->toBe([
        'x' => 999,
        'y' => 1999,
        'width' => 1,
        'height' => 1,
    ]);
});
