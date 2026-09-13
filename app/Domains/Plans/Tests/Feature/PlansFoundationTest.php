<?php

declare(strict_types=1);

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Services\Rasterizers\NullRasterizer;
use App\Domains\Projects\Services\ProjectTabRegistry;

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

it('registers the plans project tab when enabled', function (): void {
    $registry = app(ProjectTabRegistry::class);

    expect(array_keys($registry->tabs()))->toContain('plans')
        ->and(config('plans.enabled'))->toBeTrue()
        ->and(config('plans.rasterizer_driver'))->toBe('imagick');
});
