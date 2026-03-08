<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Repositories\SettingsRepository;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Support\Str;

it('uses repository and service as the supported settings data access APIs', function () {
    $key = 'cleanup.repository.'.Str::lower(Str::random(8));

    $saved = app(SettingsRepository::class)->save($key, 'cleanup-value', [
        'group' => 'cleanup',
        'display_name' => 'Cleanup Setting',
        'description' => 'Created by cleanup test',
        'type' => 'text',
        'is_visible' => true,
    ]);

    expect($saved)->not->toBeNull();
    expect(app(SettingsSqliteService::class)->get($key))->toBe('cleanup-value');
});

it('does not expose removed legacy static settings APIs on the model', function () {
    expect(method_exists(SettingsSqlite::class, 'getValue'))->toBeFalse();
    expect(method_exists(SettingsSqlite::class, 'setValue'))->toBeFalse();
    expect(method_exists(SettingsSqlite::class, 'determineType'))->toBeFalse();
    expect(method_exists(SettingsSqlite::class, 'getAllSettings'))->toBeFalse();
    expect(method_exists(SettingsSqlite::class, 'clearCache'))->toBeFalse();
    expect(method_exists(SettingsSqlite::class, 'clearAllCache'))->toBeFalse();
});
