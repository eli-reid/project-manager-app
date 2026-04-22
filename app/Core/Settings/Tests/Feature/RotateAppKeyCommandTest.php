<?php

use App\Core\Settings\Services\SettingsDatabaseProvisioner;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

it('supports dry run without modifying encrypted settings', function (): void {
    app(SettingsDatabaseProvisioner::class)->ensureDatabase();

    $oldKey = (string) config('app.key');
    $oldEncrypter = new Encrypter(base64_decode(substr($oldKey, 7)), (string) config('app.cipher'));

    $settingKey = 'tests.rotate.dry-run.'.str()->uuid();
    DB::connection('settings_sqlite')->table('settings')->insert([
        'key' => $settingKey,
        'value' => $oldEncrypter->encryptString('secret-value'),
        'display_name' => 'Rotate Dry Run',
        'description' => 'Command test',
        'type' => 'text',
        'group' => 'tests',
        'is_public' => 0,
        'is_visible' => 1,
        'is_required' => 0,
        'encrypted' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $before = DB::connection('settings_sqlite')->table('settings')->where('key', $settingKey)->value('value');

    $this->artisan('settings:rotate-app-key --dry-run')
        ->expectsOutputToContain('Encrypted settings found:')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    $after = DB::connection('settings_sqlite')->table('settings')->where('key', $settingKey)->value('value');

    expect($after)->toBe($before);

    DB::connection('settings_sqlite')->table('settings')->where('key', $settingKey)->delete();
});

it('re-encrypts encrypted settings using provided new key and updates env file', function (): void {
    app(SettingsDatabaseProvisioner::class)->ensureDatabase();

    $oldKey = (string) config('app.key');
    $oldEncrypter = new Encrypter(base64_decode(substr($oldKey, 7)), (string) config('app.cipher'));

    $newKey = 'base64:'.base64_encode(Encrypter::generateKey((string) config('app.cipher')));
    $newEncrypter = new Encrypter(base64_decode(substr($newKey, 7)), (string) config('app.cipher'));

    $settingKey = 'tests.rotate.real-run.'.str()->uuid();
    DB::connection('settings_sqlite')->table('settings')->insert([
        'key' => $settingKey,
        'value' => $oldEncrypter->encryptString('secret-value'),
        'display_name' => 'Rotate Real Run',
        'description' => 'Command test',
        'type' => 'text',
        'group' => 'tests',
        'is_public' => 0,
        'is_visible' => 1,
        'is_required' => 0,
        'encrypted' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $envPath = storage_path('framework/testing/rotate-app-key-'.str()->uuid().'.env');
    if (! is_dir(dirname($envPath))) {
        mkdir(dirname($envPath), 0755, true);
    }
    file_put_contents($envPath, "APP_NAME=Laravel\nAPP_KEY={$oldKey}\n");

    $this->artisan('settings:rotate-app-key', [
        '--new-key' => $newKey,
        '--env-file' => $envPath,
        '--only-key' => [$settingKey],
    ])
        ->expectsOutputToContain('APP_KEY rotated and encrypted settings re-encrypted successfully.')
        ->assertSuccessful();

    $rawValue = DB::connection('settings_sqlite')->table('settings')->where('key', $settingKey)->value('value');
    expect($newEncrypter->decryptString((string) $rawValue))->toBe('secret-value');

    $envContents = (string) file_get_contents($envPath);
    expect($envContents)->toContain('APP_KEY='.$newKey);

    DB::connection('settings_sqlite')->table('settings')->where('key', $settingKey)->delete();
    @unlink($envPath);
});
