<?php

use App\Core\Cpanel\Data\CpanelConfig;

it('resolves cpanel runtime config from saved settings', function (): void {

    settings()->set('cpanel.url', 'https://cpanel.runtime.test');
    settings()->set('cpanel.username', 'runtime-user');
    settings()->set('cpanel.api_token', 'runtime-token');
    settings()->set('cpanel.domain', 'runtime.test');
    settings()->set('cpanel.port', '2087');
    settings()->set('cpanel.verify_ssl', 'false');

    app()->forgetInstance(CpanelConfig::class);

    $cpanelConfig = app(CpanelConfig::class);

    expect($cpanelConfig->url)->toBe('https://cpanel.runtime.test')
        ->and($cpanelConfig->username)->toBe('runtime-user')
        ->and($cpanelConfig->apiToken)->toBe('runtime-token')
        ->and($cpanelConfig->domain)->toBe('runtime.test')
        ->and($cpanelConfig->port)->toBe(2087)
        ->and($cpanelConfig->verifySsl)->toBeFalse();

    settings()->set('cpanel.url', '');
    settings()->set('cpanel.username', '');
    settings()->set('cpanel.api_token', '');
    settings()->set('cpanel.domain', '');
    settings()->set('cpanel.port', '');
    settings()->set('cpanel.verify_ssl', '');
});
