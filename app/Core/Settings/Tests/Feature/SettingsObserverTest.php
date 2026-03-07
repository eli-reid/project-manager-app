<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Observers\SettingsObserver;
use App\Core\Settings\Services\SettingsCacheService;

it('clears current and original cache keys when a setting key and group are updated', function () {
    $cache = Mockery::mock(SettingsCacheService::class);

    $cache->shouldReceive('forgetMany')
        ->once()
        ->withArgs(function (array $keys): bool {
            $expectedKeys = [
                'setting.new.key',
                'setting.exists.new.key',
                'setting.old.key',
                'setting.exists.old.key',
                'settings.group.new-group',
                'settings.group.old-group',
                'settings.all',
                'settings.all.grouped',
                'settings.public',
            ];

            foreach ($expectedKeys as $expectedKey) {
                if (! in_array($expectedKey, $keys, true)) {
                    return false;
                }
            }

            return true;
        })
        ->andReturn(true);

    app()->instance(SettingsCacheService::class, $cache);

    $setting = new SettingsSqlite([
        'key' => 'old.key',
        'group' => 'old-group',
        'type' => 'text',
    ]);

    $setting->syncOriginal();

    $setting->key = 'new.key';
    $setting->group = 'new-group';

    $observer = app(SettingsObserver::class);

    $observer->updated($setting);
});
