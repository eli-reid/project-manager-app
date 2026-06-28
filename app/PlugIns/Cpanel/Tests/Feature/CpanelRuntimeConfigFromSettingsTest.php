<?php

use App\PlugIns\Cpanel\Data\CpanelConfig;
use App\Core\Settings\Facades\Settings;

it('resolves cpanel runtime config from saved settings', function (): void {

    Settings::set('cpanel.url', 'https://cpanel.runtime.test');
    Settings::set('cpanel.username', 'runtime-user');
    Settings::set('cpanel.api_token', 'runtime-token');
    Settings::set('cpanel.domain', 'runtime.test');
    Settings::set('cpanel.port', '2087');
    Settings::set('cpanel.verify_ssl', 'false');

    app()->forgetInstance(CpanelConfig::class);

    $cpanelConfig = app(CpanelConfig::class);

    expect($cpanelConfig->url)->toBe('https://cpanel.runtime.test')
        ->and($cpanelConfig->username)->toBe('runtime-user')
        ->and($cpanelConfig->apiToken)->toBe('runtime-token')
        ->and($cpanelConfig->domain)->toBe('runtime.test')
        ->and($cpanelConfig->port)->toBe(2087)
        ->and($cpanelConfig->verifySsl)->toBeFalse();

    Settings::set('cpanel.url', '');
    Settings::set('cpanel.username', '');
    Settings::set('cpanel.api_token', '');
    Settings::set('cpanel.domain', '');
    Settings::set('cpanel.port', '');
    Settings::set('cpanel.verify_ssl', '');
});
