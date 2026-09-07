<?php

declare(strict_types=1);

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Services\Rasterizers\NullRasterizer;

it('binds the null rasterizer in the testing environment', function (): void {
    expect(app(PlanRasterizerContract::class))
        ->toBeInstanceOf(NullRasterizer::class);
});

it('registers plans permissions', function (): void {
    $registered = app(PermissionRegistryContract::class)->permissions();

    $keys = collect($registered)->map(fn (array $permission): string => $permission['resource'].'.'.$permission['action']);

    expect($keys)
        ->toContain('plans.view')
        ->toContain('plans.compare');
});

it('renders a deterministic placeholder with the null rasterizer', function (): void {
    $path = storage_path('framework/testing/plans-placeholder.png');
    $rasterizer = app(PlanRasterizerContract::class);

    $rasterizer->renderPage('unused.pdf', 1, 150, $path);

    expect($rasterizer->pageCount('unused.pdf'))->toBe(1)
        ->and(is_file($path))->toBeTrue()
        ->and(filesize($path))->toBeGreaterThan(0);

    unlink($path);
});
