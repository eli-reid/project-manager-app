<?php

declare(strict_types=1);

use App\Domains\Plans\Services\Rasterizers\ImagickRasterizer;

it('returns correct name for imagick rasterizer', function (): void {
    $rasterizer = new ImagickRasterizer;

    expect($rasterizer->name())->toBe('imagick');
});

it('correctly reports availability without throwing iterator index exceptions', function (): void {
    $rasterizer = new ImagickRasterizer;

    $expected = extension_loaded('imagick') && in_array('PDF', array_map('strtoupper', (new Imagick)->queryFormats('PDF')), true);

    expect($rasterizer->isAvailable())->toBe($expected);
});
