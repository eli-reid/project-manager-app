<?php

use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

it('rejects invalid mailbox local part for write operations', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Http::preventStrayRequests();
    Http::fake();

    $result = $service->createEmailAccount('invalid local part', 'StrongPassword#123');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Valid email username is required.');
    Http::assertNothingSent();
});

it('masks sensitive data in cpanel request error response context and logs', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Log::spy();

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
            'status' => 0,
            'errors' => ['password=super-secret'],
            'data' => [
                'api_token' => 'secret-token',
                'nested' => [
                    'authorization' => 'cpanel root:secret-token',
                ],
            ],
        ], 200),
    ]);

    $result = $service->updateEmailPassword('john@example.test', 'StrongPassword#123');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('[REDACTED]');
    expect((string) ($result['data']['request']['password'] ?? null))->toBe('[REDACTED]');
    expect((string) ($result['data']['payload']['data']['api_token'] ?? null))->toBe('[REDACTED]');
    expect((string) ($result['data']['payload']['data']['nested']['authorization'] ?? null))->toBe('[REDACTED]');

    Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
        if ($message !== 'cPanel API request failed.') {
            return false;
        }

        $logMessage = (string) ($context['message'] ?? '');
        $requestPassword = $context['context']['request']['password'] ?? null;

        return ! str_contains($logMessage, 'super-secret')
            && $requestPassword === '[REDACTED]';
    });
});

it('lists cron jobs from cpanel', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Cron/listcron*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'linekey' => '123',
                    'minute' => '*',
                    'hour' => '*',
                    'day' => '*',
                    'month' => '*',
                    'weekday' => '*',
                    'command' => 'php artisan schedule:run',
                ],
            ],
        ]),
    ]);

    $result = $service->listCronJobs();

    expect($result['success'])->toBeTrue();
    expect($result['count'])->toBe(1);
    expect($result['cron_jobs'][0]['linekey'])->toBe('123');
    expect($result['cron_jobs'][0]['command'])->toBe('php artisan schedule:run');
});

it('ensures cron job by adding missing entry', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Cron/listcron*' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
        'https://cpanel.example.test:2083/execute/Cron/add_line' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $result = $service->ensureCronJob('*', '*', '*', '*', '*', 'php artisan schedule:run');

    expect($result['success'])->toBeTrue();
    expect($result['action'])->toBe('added');

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Cron/add_line';
    });
});

it('ensures cron job without adding when already present', function () {
    $service = new CpanelService(CpanelConfig::fromServicesConfig([
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'secret-token',
        'domain' => 'example.test',
    ]));

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Cron/listcron*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'minute' => '*',
                    'hour' => '*',
                    'day' => '*',
                    'month' => '*',
                    'weekday' => '*',
                    'command' => 'php artisan schedule:run',
                ],
            ],
        ]),
    ]);

    $result = $service->ensureCronJob('*', '*', '*', '*', '*', 'php artisan schedule:run');

    expect($result['success'])->toBeTrue();
    expect($result['action'])->toBe('exists');
});
