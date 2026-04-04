<?php

use App\Core\Settings\Models\SettingsSqlite;
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

it('uses isolated settings database in testing', function () {
    expect(config('settings-db.database_path'))->toBe('database/testing-settings.data');
});

it('populates default metadata when creating a new setting through service set', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'mail.support_email_'.Str::lower(Str::random(8));

    expect($service->set($key, 'support@example.com'))->toBeTrue();

    $setting = SettingsSqlite::query()->where('key', $key)->first();

    expect($setting)->not->toBeNull();
    expect($setting->group)->toBe('mail');
    expect($setting->type)->toBe('email');
    expect($setting->display_name)->toContain('Support Email');
    expect($setting->is_visible)->toBeTrue();
    expect($setting->is_public)->toBeFalse();
    expect($setting->is_required)->toBeFalse();
});

it('supports fluent typed reads for integer values', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'typed.int.'.Str::lower(Str::random(8));
    expect($service->set($key, '2083'))->toBeTrue();

    expect($service->get($key)->toInt())->toBe(2083);
    expect($service->get('missing.key', '42')->toInt())->toBe(42);
});

it('supports fluent typed reads for boolean values', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'typed.bool.'.Str::lower(Str::random(8));
    expect($service->set($key, 'false'))->toBeTrue();

    expect($service->get($key)->toBool(true))->toBeFalse();
    expect($service->get('missing.key', 'no')->toBool(true))->toBeFalse();
});

it('supports fluent typed reads for json array values', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'typed.json.'.Str::lower(Str::random(8));
    expect($service->set($key, '["mail","database"]'))->toBeTrue();

    expect($service->get($key)->toArray())->toBe(['mail', 'database']);
    expect($service->get('missing.key', '["sms"]')->toArray())->toBe(['sms']);
    expect($service->get('missing.invalid.json', 'not-json')->toArray(['fallback']))->toBe(['fallback']);
});

it('rejects invalid values for settings with registered type metadata', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'validation.email.'.Str::lower(Str::random(8));

    DB::connection('settings_sqlite')->table('settings')->insert([
        'key' => $key,
        'value' => 'valid@example.com',
        'display_name' => 'Support Email',
        'description' => 'Test',
        'type' => 'email',
        'group' => 'app',
        'order' => 1,
        'is_public' => 0,
        'is_visible' => 1,
        'is_required' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => $service->set($key, 'not-an-email'))
        ->toThrow(InvalidArgumentException::class);
});

it('allows writing new settings keys that have no registered metadata', function () {
    $service = app(SettingsSqliteService::class);
    $service->clearAllCache();

    $key = 'brand.new.key.'.Str::lower(Str::random(8));

    expect($service->set($key, 'some-value'))->toBeTrue();
    expect($service->get($key)->raw())->toBe('some-value');
});
