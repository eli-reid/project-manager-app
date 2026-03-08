<?php

use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns a configuration error when cpanel credentials are missing', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([]));

    $result = $service->listEmailAccounts();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('cPanel configuration is incomplete.');
});

it('lists and normalizes mailbox accounts from cpanel', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
        'port' => 2083,
        'verify_ssl' => true,
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_pops_with_disk*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'email' => 'jane',
                    'domain' => 'example.test',
                    'diskquota' => '250',
                    'diskused' => '40M',
                    'suspended_login' => 0,
                ],
            ],
        ]),
    ]);

    $result = $service->listEmailAccounts();

    expect($result['success'])->toBeTrue();
    expect($result['count'])->toBe(1);
    expect($result['emails'][0]['email'])->toBe('jane@example.test');
    expect($result['emails'][0]['quota'])->toBe(250);
    expect($result['emails'][0]['usage'])->toBe(40);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && str_contains($request->url(), 'https://cpanel.example.test:2083/execute/Email/list_pops_with_disk')
            && str_contains($request->url(), 'regex=%40example.test')
            && $request->hasHeader('Authorization', 'cpanel root:secret-token');
    });
});

it('creates a mailbox account with default quota', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
        'default_email_quota' => 250,
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $result = $service->createEmailAccount('john', 'StrongPassword#123');

    expect($result['success'])->toBeTrue();
    expect($result['email'])->toBe('john@example.test');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_pop'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['quota'] ?? null) === 250
            && $request->hasHeader('Authorization', 'cpanel root:secret-token');
    });
});

it('deletes mailbox account by local-part when full email is provided', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/delete_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $result = $service->deleteEmailAccount('john@example.test');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/delete_pop'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test';
    });
});

it('falls back to webmail redirect url when session api does not return url', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
        'webmail_port' => 2096,
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Session/create_webmail_session_for_self' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $result = $service->createWebmailSession('john@example.test');

    expect($result['success'])->toBeTrue();
    expect($result['url'])->toBe('https://cpanel.example.test:2096/?user=john%40example.test');
});
