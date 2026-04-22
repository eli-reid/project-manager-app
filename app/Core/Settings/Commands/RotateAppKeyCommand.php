<?php

namespace App\Core\Settings\Commands;

use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Throwable;

class RotateAppKeyCommand extends Command
{
    protected $signature = 'settings:rotate-app-key
        {--new-key= : New APP_KEY value. If omitted, a secure key is generated}
        {--env-file=.env : Environment file path to update APP_KEY in}
        {--only-key=* : Limit re-encryption to specific setting keys}
        {--dry-run : Preview changes without writing settings or env file}
        {--force : Allow running in production}';

    protected $description = 'Rotate APP_KEY and re-encrypt encrypted settings values using the new key';

    public function handle(SettingsSqliteService $settingsService): int
    {
        if (app()->environment('production') && ! (bool) $this->option('force')) {
            $this->error('Refusing to rotate APP_KEY in production without --force.');

            return self::FAILURE;
        }

        $oldKey = (string) config('app.key');
        if ($oldKey === '') {
            $this->error('Current APP_KEY is empty. Aborting.');

            return self::FAILURE;
        }

        $newKey = (string) ($this->option('new-key') ?: $this->generateAppKey());
        if ($newKey === $oldKey) {
            $this->error('New key must be different from the current APP_KEY.');

            return self::FAILURE;
        }

        try {
            $oldEncrypter = $this->makeEncrypter($oldKey);
            $newEncrypter = $this->makeEncrypter($newKey);
        } catch (Throwable $exception) {
            $this->error('Failed to initialize encrypter: '.$exception->getMessage());

            return self::FAILURE;
        }

        $encryptedSettingsQuery = DB::connection('settings_sqlite')
            ->table('settings')
            ->where('encrypted', true)
            ->whereNotNull('value');

        $onlyKeys = collect((array) $this->option('only-key'))
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->values();

        if ($onlyKeys->isNotEmpty()) {
            $encryptedSettingsQuery->whereIn('key', $onlyKeys->all());
        }

        $encryptedSettings = $encryptedSettingsQuery->get(['id', 'key', 'value']);

        $this->info('Encrypted settings found: '.$encryptedSettings->count());
        if ((bool) $this->option('dry-run')) {
            $this->line('Dry run: APP_KEY would be rotated and encrypted settings would be re-encrypted.');

            return self::SUCCESS;
        }

        DB::connection('settings_sqlite')->beginTransaction();

        try {
            foreach ($encryptedSettings as $setting) {
                $decryptedValue = $oldEncrypter->decryptString((string) $setting->value);
                $reEncryptedValue = $newEncrypter->encryptString($decryptedValue);

                DB::connection('settings_sqlite')
                    ->table('settings')
                    ->where('id', $setting->id)
                    ->update([
                        'value' => $reEncryptedValue,
                        'updated_at' => now(),
                    ]);
            }

            DB::connection('settings_sqlite')->commit();
        } catch (Throwable $exception) {
            DB::connection('settings_sqlite')->rollBack();
            $this->error('Re-encryption failed. No settings were changed: '.$exception->getMessage());

            return self::FAILURE;
        }

        try {
            $this->updateEnvKey((string) $this->option('env-file'), $newKey);
        } catch (Throwable $exception) {
            $this->error('Settings were re-encrypted but APP_KEY update failed: '.$exception->getMessage());
            $this->warn('Manually set APP_KEY to: '.$newKey);

            return self::FAILURE;
        }

        config()->set('app.key', $newKey);
        $settingsService->clearAllCache();

        $this->info('APP_KEY rotated and encrypted settings re-encrypted successfully.');
        $this->line('New APP_KEY: '.$newKey);

        return self::SUCCESS;
    }

    private function makeEncrypter(string $appKey): Encrypter
    {
        $cipher = (string) config('app.cipher', 'AES-256-CBC');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded === false) {
                throw new \RuntimeException('Invalid base64 APP_KEY format.');
            }

            return new Encrypter($decoded, $cipher);
        }

        return new Encrypter($appKey, $cipher);
    }

    private function generateAppKey(): string
    {
        $cipher = (string) config('app.cipher', 'AES-256-CBC');
        $key = Encrypter::generateKey($cipher);

        return 'base64:'.base64_encode($key);
    }

    private function updateEnvKey(string $envFile, string $newKey): void
    {
        if (! file_exists($envFile)) {
            throw new \RuntimeException("Env file not found: {$envFile}");
        }

        $contents = (string) file_get_contents($envFile);
        if ($contents === '') {
            throw new \RuntimeException("Unable to read env file: {$envFile}");
        }

        if (preg_match('/^APP_KEY=.*$/m', $contents) === 1) {
            $updated = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$newKey, $contents, 1);
        } else {
            $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
            $updated = rtrim($contents).$lineEnding.'APP_KEY='.$newKey.$lineEnding;
        }

        file_put_contents($envFile, (string) $updated);
    }
}
