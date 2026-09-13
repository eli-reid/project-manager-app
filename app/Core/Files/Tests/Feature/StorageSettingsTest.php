<?php

use App\Core\Files\Providers\FilesServiceProvider;
use App\Core\Settings\Facades\Settings;
use App\Core\Settings\Services\DomainSettingsSynchronizer;

it('defines default storage settings keys', function () {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)->toContain('storage.default_disk');
    expect($keys)->toContain('storage.s3.access_key_id');
    expect($keys)->toContain('storage.s3.secret_access_key');
    expect($keys)->toContain('storage.s3.region');
    expect($keys)->toContain('storage.s3.bucket');
    expect($keys)->toContain('storage.s3.endpoint');
    expect($keys)->toContain('storage.s3.use_path_style_endpoint');
    expect($keys)->toContain('storage.sftp.host');
});

it('applies saved S3 settings onto the filesystems config', function () {
    Settings::set('storage.s3.access_key_id', 'AKIAEXAMPLE');
    Settings::set('storage.s3.secret_access_key', 'super-secret');
    Settings::set('storage.s3.region', 'us-east-2');
    Settings::set('storage.s3.bucket', 'my-bucket');
    Settings::set('storage.s3.use_path_style_endpoint', 'true');

    $provider = new FilesServiceProvider(app());
    app()->call([$provider, 'boot']);

    expect(config('filesystems.disks.s3.key'))->toBe('AKIAEXAMPLE');
    expect(config('filesystems.disks.s3.secret'))->toBe('super-secret');
    expect(config('filesystems.disks.s3.region'))->toBe('us-east-2');
    expect(config('filesystems.disks.s3.bucket'))->toBe('my-bucket');
    expect(config('filesystems.disks.s3.use_path_style_endpoint'))->toBeTrue();
});
