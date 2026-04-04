<?php

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsRegistry;
use Illuminate\Support\Str;

it('binds settings registry contract to concrete implementation', function () {
    $registry = app(SettingsRegistryContract::class);

    expect($registry)->toBeInstanceOf(SettingsRegistry::class);
});

it('discovers core app settings definitions', function () {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)->toContain('app.name');
    expect($keys)->toContain('session.driver');
    expect($keys)->toContain('system.locale');
    expect($keys)->toContain('mail.mailer');
    expect($keys)->toContain('mail.from_address');
    expect($keys)->toContain('security.session_timeout');
    expect($keys)->toContain('features.maintenance_mode');
});

it('prunes undefined settings when prune mode is enabled', function () {
    $orphanKey = 'orphan.setting.'.Str::lower(Str::random(8));

    SettingsSqlite::query()->create([
        'key' => $orphanKey,
        'value' => 'orphan-value',
        'default_value' => 'orphan-value',
        'display_name' => 'Orphan Setting',
        'description' => 'Temporary test orphan setting',
        'type' => 'text',
        'group' => 'orphan',
        'order' => 1,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => false,
        'encrypted' => false,
    ]);

    $changes = app(DomainSettingsSynchronizer::class)->sync(pruneUndefined: true);

    expect($changes)->toBeGreaterThan(0);
    expect(SettingsSqlite::query()->where('key', $orphanKey)->exists())->toBeFalse();
    expect(SettingsSqlite::query()->where('key', 'app.name')->exists())->toBeTrue();
});
