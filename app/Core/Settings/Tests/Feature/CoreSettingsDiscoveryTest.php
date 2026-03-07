<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use Illuminate\Support\Str;

it('discovers core app settings definitions', function () {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)->toContain('app.name');
    expect($keys)->toContain('session.driver');
    expect($keys)->toContain('system.locale');
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
