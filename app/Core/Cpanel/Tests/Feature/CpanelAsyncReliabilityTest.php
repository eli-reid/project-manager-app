<?php

use App\Core\Cpanel\Jobs\PerformMailboxWriteOperation;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('queues write-side lifecycle mailbox operations when queueing is enabled', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'auto_create_emails' => true,
        'auto_delete_emails' => true,
        'queue_write_operations' => true,
        'verify_ssl' => true,
    ]);

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
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'auto_delete_emails' => true,
        'queue_write_operations' => false,
        'idempotency_ttl_seconds' => 300,
        'verify_ssl' => true,
    ]);

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

    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'auto_delete_emails' => true,
        'queue_write_operations' => false,
        'failure_threshold' => 1,
        'cooldown_seconds' => 120,
        'telemetry_key_prefix' => $prefix,
        'verify_ssl' => true,
    ]);

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
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'queue_write_operations' => false,
        'failure_threshold' => 10,
        'verify_ssl' => true,
    ]);

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
    ))->toThrow(\RuntimeException::class);
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
