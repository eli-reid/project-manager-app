<?php

use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('returns only public settings even after preloading all settings', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $publicKey = 'public.setting.'.Str::lower(Str::random(8));
    $privateKey = 'private.setting.'.Str::lower(Str::random(8));

    DB::connection('settings_sqlite')->table('settings')->insert([
        [
            'key' => $publicKey,
            'value' => 'public-value',
            'display_name' => 'Public Setting',
            'description' => 'Public setting',
            'type' => 'text',
            'group' => 'app',
            'order' => 1,
            'is_public' => 1,
            'is_visible' => 1,
            'is_required' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'key' => $privateKey,
            'value' => 'private-value',
            'display_name' => 'Private Setting',
            'description' => 'Private setting',
            'type' => 'text',
            'group' => 'app',
            'order' => 2,
            'is_public' => 0,
            'is_visible' => 1,
            'is_required' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $service->preloadAllSettings();
    $publicSettings = $service->getPublicSettings();

    expect($publicSettings->pluck('key')->all())
        ->toContain($publicKey)
        ->not->toContain($privateKey);
});

it('resets in-memory preload cache after setting updates', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'cache.reset.setting.'.Str::lower(Str::random(8));

    DB::connection('settings_sqlite')->table('settings')->insert([
        'key' => $key,
        'value' => 'Old Name',
        'display_name' => 'Application Name',
        'description' => 'Application name',
        'type' => 'text',
        'group' => 'app',
        'order' => 1,
        'is_public' => 0,
        'is_visible' => 1,
        'is_required' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $initialSettings = $service->preloadAllSettings();
    expect($initialSettings->get($key))->toBe('Old Name');

    expect($service->set($key, 'New Name'))->toBeTrue();

    $reloadedSettings = $service->preloadAllSettings();
    expect($reloadedSettings->get($key))->toBe('New Name');
});
