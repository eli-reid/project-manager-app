<?php

use App\Core\Settings\Facades\Settings;
use App\Core\Settings\Services\DomainSettingsSynchronizer;

it('defines default document settings keys', function () {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)
        ->pluck('key')
        ->all();

    expect($keys)->toContain('documents.allowed_types');
    expect($keys)->toContain('documents.max_file_size');
    expect($keys)->toContain('documents.enable_versioning');
    expect($keys)->toContain('documents.replace_behavior');
    expect($keys)->toContain('documents.storage_disk');
});

it('loads domain settings definitions from the settings registry', function () {
    $synchronizer = app(DomainSettingsSynchronizer::class);

    $definitions = $synchronizer->loadDefinitions();

    expect($definitions)->not->toBeEmpty();
    expect(collect($definitions)->pluck('key')->all())->toContain('documents.storage_disk');
});

it('syncs domain settings without overwriting existing values by default', function () {
    Settings::set('documents.storage_disk', 's3');

    $synchronizer = app(DomainSettingsSynchronizer::class);
    $synchronizer->sync();

    $definitions = collect($synchronizer->loadDefinitions());
    $storageDiskDefinition = $definitions->firstWhere('key', 'documents.storage_disk');

    $value = Settings::get('documents.storage_disk', 'local')->toString();

    expect($storageDiskDefinition)->not->toBeNull();
    expect((string) ($storageDiskDefinition['default_value'] ?? ''))->toBe('local');
    expect($value)->toBe('s3');
});
