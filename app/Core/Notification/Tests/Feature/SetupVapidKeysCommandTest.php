<?php

it('writes provided vapid keys to the target env file', function (): void {
    $envPath = storage_path('framework/testing/vapid-setup-'.str()->uuid().'.env');

    if (! is_dir(dirname($envPath))) {
        mkdir(dirname($envPath), 0755, true);
    }

    file_put_contents($envPath, "APP_NAME=Laravel\n");

    $this->artisan('notifications:vapid:setup', [
        '--public-key' => 'public-test-key',
        '--private-key' => 'private-test-key',
        '--subject' => 'mailto:notify@example.test',
        '--env-file' => $envPath,
    ])
        ->expectsOutputToContain('VAPID keys configured successfully.')
        ->assertSuccessful();

    $contents = (string) file_get_contents($envPath);

    expect($contents)
        ->toContain('VAPID_PUBLIC_KEY=public-test-key')
        ->toContain('VAPID_PRIVATE_KEY=private-test-key')
        ->toContain('VAPID_SUBJECT=mailto:notify@example.test');

    @unlink($envPath);
});

it('refuses to overwrite existing keys without force', function (): void {
    $envPath = storage_path('framework/testing/vapid-setup-existing-'.str()->uuid().'.env');

    if (! is_dir(dirname($envPath))) {
        mkdir(dirname($envPath), 0755, true);
    }

    file_put_contents($envPath, "APP_NAME=Laravel\nVAPID_PUBLIC_KEY=existing-public\nVAPID_PRIVATE_KEY=existing-private\n");

    $this->artisan('notifications:vapid:setup', [
        '--public-key' => 'new-public',
        '--private-key' => 'new-private',
        '--env-file' => $envPath,
    ])
        ->expectsOutputToContain('VAPID keys already exist. Use --force to replace them.')
        ->assertFailed();

    $contents = (string) file_get_contents($envPath);

    expect($contents)
        ->toContain('VAPID_PUBLIC_KEY=existing-public')
        ->toContain('VAPID_PRIVATE_KEY=existing-private')
        ->not->toContain('VAPID_PUBLIC_KEY=new-public');

    @unlink($envPath);
});
