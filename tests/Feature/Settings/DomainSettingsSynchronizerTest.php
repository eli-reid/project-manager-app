<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;

it('loads domain settings definitions from domain config folders', function () {
    $synchronizer = app(DomainSettingsSynchronizer::class);

    $definitions = $synchronizer->loadDefinitions();

    expect($definitions)->not->toBeEmpty();
    expect(collect($definitions)->pluck('key')->all())->toContain('documents.storage_disk');
});

it('syncs domain settings without overwriting existing values by default', function () {
    SettingsSqlite::query()->updateOrCreate(
        ['key' => 'documents.storage_disk'],
        [
            'value' => 's3',
            'default_value' => 's3',
            'display_name' => 'Document Storage Disk',
            'description' => 'Storage disk for project documents',
            'type' => 'select',
            'group' => 'documents',
            'options' => json_encode(['local' => 'Local Storage', 's3' => 'Amazon S3']),
            'order' => 4,
            'is_public' => false,
            'is_visible' => true,
            'is_required' => false,
            'encrypted' => false,
        ]
    );

    $synchronizer = app(DomainSettingsSynchronizer::class);
    $synchronizer->sync();

    $setting = SettingsSqlite::query()->where('key', 'documents.storage_disk')->first();

    expect($setting)->not->toBeNull();
    expect($setting?->value)->toBe('s3');
    expect($setting?->default_value)->toBe('local');
});
