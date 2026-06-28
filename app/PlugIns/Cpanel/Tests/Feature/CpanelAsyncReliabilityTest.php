<?php

use App\PlugIns\Cpanel\Jobs\PerformMailboxWriteOperation;
use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('queues write-side lifecycle mailbox operations when queueing is enabled', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'true');
    Settings::set('cpanel.auto_delete_emails', 'true');
    Settings::set('cpanel.queue_write_operations', 'true');
    Settings::set('cpanel.verify_ssl', 'true');

    Queue::fake();

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => null,
    ]);

    Queue::assertPushed(PerformMailboxWriteOperation::class, function (PerformMailboxWriteOperation $job): bool {
        return $job->operation === CpanelMailboxManager::OPERATION_PROVISION
            && ($job->payload['username'] ?? null) === 'jane';
    });

    $user->delete();

    Queue::assertPushed(PerformMailboxWriteOperation::class, function (PerformMailboxWriteOperation $job): bool {
        return $job->operation === CpanelMailboxManager::OPERATION_DELETE
            && ($job->payload['email'] ?? null) === 'jane@example.test';
    });
});

it('deduplicates repeated delete operations within idempotency window', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'false');
    Settings::set('cpanel.auto_delete_emails', 'true');
    Settings::set('cpanel.queue_write_operations', 'false');
    Settings::set('cpanel.idempotency_ttl_seconds', '300');
    Settings::set('cpanel.verify_ssl', 'true');

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/delete_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    /** @var CpanelMailboxManager $manager */
    $manager = app(CpanelMailboxManager::class);

    $manager->deprovisionForUser($user);
    $manager->deprovisionForUser($user);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/delete_pop'
            && ($data['email'] ?? null) === 'jane';
    });
});

it('opens cooldown and tracks telemetry counters on repeated failures', function () {
    $prefix = 'cpanel.telemetry.test.'.uniqid('', true);

    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'false');
    Settings::set('cpanel.auto_delete_emails', 'true');
    Settings::set('cpanel.queue_write_operations', 'false');
    Settings::set('cpanel.failure_threshold', '1');
    Settings::set('cpanel.cooldown_seconds', '120');
    Settings::set('cpanel.telemetry_key_prefix', $prefix);
    Settings::set('cpanel.verify_ssl', 'true');

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/delete_pop' => Http::response([
            'status' => 0,
            'errors' => ['Unable to connect to cPanel API.'],
        ], 200),
    ]);

    $firstUser = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    $secondUser = User::factory()->create([
        'username' => 'john',
        'company_email' => 'john@example.test',
    ]);

    /** @var CpanelMailboxManager $manager */
    $manager = app(CpanelMailboxManager::class);

    $manager->deprovisionForUser($firstUser);
    $manager->deprovisionForUser($secondUser);

    Http::assertSentCount(1);

    expect((int) Cache::get($prefix.'.failure.'.CpanelMailboxManager::OPERATION_DELETE, 0))->toBe(1)
        ->and((int) Cache::get($prefix.'.skipped.'.CpanelMailboxManager::OPERATION_DELETE, 0))->toBe(1)
        ->and((int) Cache::get($prefix.'.cooldown_until', 0))->toBeGreaterThan(now()->timestamp);
});

it('throws transient mailbox failures in queued execution to trigger retries', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.queue_write_operations', 'false');
    Settings::set('cpanel.failure_threshold', '10');
    Settings::set('cpanel.verify_ssl', 'true');

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/delete_pop' => Http::response([
            'status' => 0,
            'errors' => ['Unable to connect to cPanel API.'],
        ], 200),
    ]);

    /** @var CpanelMailboxManager $manager */
    $manager = app(CpanelMailboxManager::class);

    expect(fn () => $manager->executeWriteOperation(
        operation: CpanelMailboxManager::OPERATION_DELETE,
        payload: ['email' => 'jane@example.test'],
        fromQueue: true,
    ))->toThrow(RuntimeException::class);
});

it('uses configured queue tries and backoff values for mailbox jobs', function () {
    config()->set('services.cpanel.queue_tries', 4);
    config()->set('services.cpanel.queue_backoff', '5,15,45');

    $job = new PerformMailboxWriteOperation(CpanelMailboxManager::OPERATION_DELETE, [
        'email' => 'jane@example.test',
    ]);

    expect($job->tries)->toBe(4)
        ->and($job->backoff())->toBe([5, 15, 45]);
});
