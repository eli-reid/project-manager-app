<?php

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\Contracts\FilePathNormalizerContract;
use App\Core\Assets\Contracts\FileStorageContract;
use App\Core\Assets\Services\AssetGatekeeper;
use App\Core\Assets\Services\AssetReferencerRegistry;
use App\Core\Assets\Services\AssetService;
use App\Core\Assets\Services\DefaultFilePathNormalizer;
use App\Core\Assets\Services\LaravelFileStorage;
use App\Core\Settings\Services\DomainSettingsSynchronizer;

it('binds the asset orchestrator contract', function (): void {
    expect(app(AssetOrchestratorContract::class))->toBeInstanceOf(AssetService::class);
});

it('binds the file storage contract', function (): void {
    expect(app(FileStorageContract::class))->toBeInstanceOf(LaravelFileStorage::class);
});

it('binds the file path normalizer contract', function (): void {
    expect(app(FilePathNormalizerContract::class))->toBeInstanceOf(DefaultFilePathNormalizer::class);
});

it('shares a single referencer registry instance', function (): void {
    expect(app(AssetReferencerRegistry::class))->toBe(app(AssetReferencerRegistry::class));
});

it('resolves the gatekeeper', function (): void {
    expect(app(AssetGatekeeper::class))->toBeInstanceOf(AssetGatekeeper::class);
});

it('registers assets settings definitions', function (): void {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)->toContain('assets.storage_disk')
        ->and($keys)->toContain('assets.allowed_types')
        ->and($keys)->toContain('assets.max_file_size');
});
