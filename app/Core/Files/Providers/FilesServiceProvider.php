<?php

namespace App\Core\Files\Providers;

use App\Core\Files\Contracts\FilePathNormalizerContract;
use App\Core\Files\Contracts\FileStorageContract;
use App\Core\Files\Services\DefaultFilePathNormalizer;
use App\Core\Files\Services\LaravelFileStorage;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\Facades\Settings;
use Illuminate\Support\ServiceProvider;

class FilesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileStorageContract::class, LaravelFileStorage::class);
        $this->app->singleton(FilePathNormalizerContract::class, DefaultFilePathNormalizer::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('storage', __DIR__.'/../config/settings.php');
        $this->applyStorageSettings();
    }

    private function applyStorageSettings(): void
    {
        try {
            $default = Settings::get('storage.default_disk', null)->raw();

            if (is_string($default) && trim($default) !== '') {
                config()->set('filesystems.default', $default);
            }

            $this->applySettingIfPresent('storage.s3.access_key_id', 'filesystems.disks.s3.key');
            $this->applySettingIfPresent('storage.s3.secret_access_key', 'filesystems.disks.s3.secret');
            $this->applySettingIfPresent('storage.s3.region', 'filesystems.disks.s3.region');
            $this->applySettingIfPresent('storage.s3.bucket', 'filesystems.disks.s3.bucket');
            $this->applySettingIfPresent('storage.s3.url', 'filesystems.disks.s3.url');
            $this->applySettingIfPresent('storage.s3.endpoint', 'filesystems.disks.s3.endpoint');
            $this->applyBooleanSettingIfPresent('storage.s3.use_path_style_endpoint', 'filesystems.disks.s3.use_path_style_endpoint');

            $this->applySettingIfPresent('storage.sftp.host', 'filesystems.disks.sftp.host');
            $this->applySettingIfPresent('storage.sftp.username', 'filesystems.disks.sftp.username');
            $this->applySettingIfPresent('storage.sftp.password', 'filesystems.disks.sftp.password');
            $this->applySettingIfPresent('storage.sftp.private_key', 'filesystems.disks.sftp.privateKey');
            $this->applySettingIfPresent('storage.sftp.root', 'filesystems.disks.sftp.root');

            $port = Settings::get('storage.sftp.port', null)->raw();

            if (is_numeric($port)) {
                config()->set('filesystems.disks.sftp.port', (int) $port);
            }
        } catch (\Throwable) {
            // Settings table may not be migrated/seeded yet (e.g. during install); fall back to env-driven config.
        }
    }

    private function applySettingIfPresent(string $settingKey, string $configKey): void
    {
        $value = Settings::get($settingKey, null)->raw();

        if (is_string($value) && trim($value) !== '') {
            config()->set($configKey, $value);
        }
    }

    private function applyBooleanSettingIfPresent(string $settingKey, string $configKey): void
    {
        $value = Settings::get($settingKey, null)->raw();

        if ($value === 'true' || $value === 'false') {
            config()->set($configKey, $value === 'true');
        }
    }
}
