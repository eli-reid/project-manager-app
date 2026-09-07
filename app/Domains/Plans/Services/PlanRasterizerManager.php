<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Services\Rasterizers\ImagickRasterizer;
use App\Domains\Plans\Services\Rasterizers\NullRasterizer;
use App\Domains\Plans\Services\Rasterizers\PopplerRasterizer;
use Illuminate\Support\Manager;

final class PlanRasterizerManager extends Manager
{
    public function createPopplerDriver(): PopplerRasterizer
    {
        return new PopplerRasterizer;
    }

    public function createImagickDriver(): ImagickRasterizer
    {
        return new ImagickRasterizer;
    }

    public function createNullDriver(): NullRasterizer
    {
        return new NullRasterizer;
    }

    public function getDefaultDriver(): string
    {
        return (string) config('plans.rasterizer_driver', 'poppler');
    }
}
