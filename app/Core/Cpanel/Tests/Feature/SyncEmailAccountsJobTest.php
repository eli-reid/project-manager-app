<?php

use App\Core\Cpanel\Jobs\SyncEmailAccountsJob;
use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function resetCpanelSettingsForTest(): void
{
    settings()->set('cpanel.url', '');
    settings()->set('cpanel.username', '');
    settings()->set('cpanel.api_token', '');
    settings()->set('cpanel.domain', '');
}

function setCpanelSettingsForTest(): void
{
    settings()->set('cpanel.url', 'https://cpanel.example.test');
    settings()->set('cpanel.username', 'root');
    settings()->set('cpanel.api_token', 'token-123');
    settings()->set('cpanel.domain', 'example.test');
}

it('syncs cpanel mailbox accounts into cached_email_accounts', function () {
    resetCpanelSettingsForTest();
    setCpanelSettingsForTest();

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_pops_with_disk*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'email' => 'jane',
                    'domain' => 'example.test',
                    'diskquota' => '250',
                    'diskused' => '50M',
                    'suspended_login' => 0,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    SyncEmailAccountsJob::dispatchSync();

    $record = CachedEmailAccount::query()->first();

    expect($record)->not->toBeNull()
        ->and($record?->email)->toBe('jane@example.test')
        ->and($record?->domain)->toBe('example.test')
        ->and($record?->quota)->toBe(250)
        ->and($record?->usage)->toBe(50)
        ->and($record?->suspended)->toBeFalse()
        ->and($record?->user_id)->toBe($user->id)
        ->and($record?->sync_failed)->toBeFalse();

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && str_contains($request->url(), '/execute/Email/list_pops_with_disk');
    });
});

it('queues cpanel sync command when queue option is used', function () {
    resetCpanelSettingsForTest();
    setCpanelSettingsForTest();

    Queue::fake();

    $this->artisan('cpanel:sync-emails --queue')
        ->assertSuccessful();

    Queue::assertPushed(SyncEmailAccountsJob::class);
});

it('fails cpanel sync command when cpanel config is missing', function () {
    resetCpanelSettingsForTest();

    $this->artisan('cpanel:sync-emails')
        ->assertFailed();
});
