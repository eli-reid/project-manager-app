<?php

use App\Core\Cpanel\Jobs\SyncEmailAccountsJob;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Create as EmailAccountsCreate;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Index as EmailAccountsIndex;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Show as EmailAccountsShow;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\Dashboard;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\DomainForwarders as EmailManagementDomainForwarders;
use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('forbids cpanel manage livewire routes for users without access', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.cpanel.manage.dashboard'))
        ->assertForbidden();

    actingAs($user)
        ->get(route('admin.cpanel.manage.email-accounts.index'))
        ->assertForbidden();

    actingAs($user)
        ->get(route('admin.cpanel.manage.email-accounts.create'))
        ->assertForbidden();

    actingAs($user)
        ->get(route('admin.cpanel.manage.domain-forwarders'))
        ->assertForbidden();

    $cachedAccount = CachedEmailAccount::factory()->create();

    actingAs($user)
        ->get(route('admin.cpanel.manage.email-accounts.show', $cachedAccount))
        ->assertForbidden();
});

it('renders cpanel management dashboard for admins and queues sync action', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    CachedEmailAccount::factory()->count(2)->create();

    actingAs($admin)
        ->get(route('admin.cpanel.manage.dashboard'))
        ->assertSuccessful()
        ->assertSee('Email Management')
        ->assertSee('Total Accounts')
        ->assertSee('2');

    Queue::fake();

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->call('triggerSync');

    Queue::assertPushed(SyncEmailAccountsJob::class);
});

it('renders cached email account index for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
        'suspended' => false,
        'quota' => 250,
        'usage' => 20,
        'usage_percentage' => 8,
    ]);

    actingAs($admin)
        ->get(route('admin.cpanel.manage.email-accounts.index'))
        ->assertSuccessful()
        ->assertSee('Email Accounts')
        ->assertSee('jane@example.test');

    Livewire::actingAs($admin)
        ->test(EmailAccountsIndex::class)
        ->set('search', 'jane@example.test')
        ->assertSet('search', 'jane@example.test');
});

it('renders mailbox create screen for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.cpanel.manage.email-accounts.create'))
        ->assertSuccessful()
        ->assertSee('Create Mailbox');
});

it('creates mailbox from create screen and caches it', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'default_email_quota' => 250,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsCreate::class)
        ->set('username', 'new.user')
        ->set('password', 'StrongPassword#123')
        ->set('quota', 400)
        ->call('createMailbox')
        ->assertHasNoErrors();

    expect(CachedEmailAccount::query()->where('email', 'new.user@example.test')->exists())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_pop'
            && ($data['email'] ?? null) === 'new.user'
            && ($data['domain'] ?? null) === 'example.test'
            && (int) ($data['quota'] ?? 0) === 400;
    });
});

it('renders mailbox detail screen for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    actingAs($admin)
        ->get(route('admin.cpanel.manage.email-accounts.show', $cachedAccount))
        ->assertSuccessful()
        ->assertSee('Mailbox Details')
        ->assertSee('jane@example.test')
        ->assertSee('Forwarders');
});

it('resets mailbox password from detail screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_autoresponders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_filters*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsShow::class, ['cachedEmailAccount' => $cachedAccount])
        ->set('newPassword', 'StrongPassword#456')
        ->call('resetMailboxPassword')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/passwd_pop'
            && ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['password'] ?? null) === 'StrongPassword#456';
    });
});

it('launches webmail from detail screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_autoresponders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_filters*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Session/create_webmail_session_for_self' => Http::response([
            'status' => 1,
            'data' => [
                'url' => 'https://webmail.example.test/session/abc123',
            ],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsShow::class, ['cachedEmailAccount' => $cachedAccount])
        ->call('launchWebmail')
        ->assertRedirect('https://webmail.example.test/session/abc123');

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Session/create_webmail_session_for_self') {
            return false;
        }

        $data = $request->data();

        return ($data['user'] ?? null) === 'jane@example.test';
    });
});

it('adds and deletes forwarders from detail screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'email' => 'jane@example.test',
                    'forward' => 'ops@example.test',
                ],
            ],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_autoresponders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_filters*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/add_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/delete_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsShow::class, ['cachedEmailAccount' => $cachedAccount])
        ->set('forwardTo', 'ops@example.test')
        ->call('addForwarder')
        ->assertHasNoErrors()
        ->call('deleteForwarder', 'ops@example.test')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/add_forwarder') {
            return false;
        }

        $data = $request->data();

        return ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['fwdemail'] ?? null) === 'ops@example.test';
    });

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/delete_forwarder') {
            return false;
        }

        $data = $request->data();

        return ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['fwdemail'] ?? null) === 'ops@example.test';
    });
});

it('adds and deletes autoresponder from detail screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_autoresponders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_filters*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/add_autoresponder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/delete_autoresponder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsShow::class, ['cachedEmailAccount' => $cachedAccount])
        ->set('autoresponderSubject', 'Out of Office')
        ->set('autoresponderBody', 'Back on Monday.')
        ->call('addAutoresponder')
        ->assertHasNoErrors()
        ->call('deleteAutoresponder')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/add_autoresponder') {
            return false;
        }

        $data = $request->data();

        return ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['subject'] ?? null) === 'Out of Office'
            && ($data['body'] ?? null) === 'Back on Monday.';
    });

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/delete_autoresponder') {
            return false;
        }

        $data = $request->data();

        return ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test';
    });
});

it('adds and deletes filters from detail screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_autoresponders*' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/list_filters*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'filtername' => 'Route Finance',
                ],
            ],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/store_filter' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/delete_filter' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $cachedAccount = CachedEmailAccount::factory()->create([
        'email' => 'jane@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsShow::class, ['cachedEmailAccount' => $cachedAccount])
        ->set('filterName', 'Route Finance')
        ->set('filterFromContains', 'finance@vendor.com')
        ->set('filterForwardTo', 'ops@example.test')
        ->call('addFilter')
        ->assertHasNoErrors()
        ->call('deleteFilter', 'Route Finance')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/store_filter') {
            return false;
        }

        $data = $request->data();

        return ($data['account'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['filtername'] ?? null) === 'Route Finance'
            && ($data['val1'] ?? null) === 'finance@vendor.com'
            && ($data['dest1'] ?? null) === 'ops@example.test';
    });

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/delete_filter') {
            return false;
        }

        $data = $request->data();

        return ($data['account'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['filtername'] ?? null) === 'Route Finance';
    });
});

it('renders domain forwarders screen for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.cpanel.manage.domain-forwarders'))
        ->assertSuccessful()
        ->assertSee('Domain Forwarders');
});

it('adds and deletes domain forwarders from management screen', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_domain_forwarders*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'domain' => 'example.test',
                    'destdomain' => 'example.org',
                ],
            ],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/add_domain_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/delete_domain_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(EmailManagementDomainForwarders::class)
        ->set('sourceDomain', 'example.test')
        ->set('destinationDomain', 'example.org')
        ->call('addDomainForwarder')
        ->assertHasNoErrors()
        ->call('deleteDomainForwarder', 'example.test')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/add_domain_forwarder') {
            return false;
        }

        $data = $request->data();

        return ($data['domain'] ?? null) === 'example.test'
            && ($data['destdomain'] ?? null) === 'example.org';
    });

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://cpanel.example.test:2083/execute/Email/delete_domain_forwarder') {
            return false;
        }

        $data = $request->data();

        return ($data['domain'] ?? null) === 'example.test';
    });
});

it('processes bulk suspend, unsuspend, and password reset from accounts index', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/suspend_login' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/unsuspend_login' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
        'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $accountOne = CachedEmailAccount::factory()->create(['email' => 'one@example.test', 'suspended' => false]);
    $accountTwo = CachedEmailAccount::factory()->create(['email' => 'two@example.test', 'suspended' => false]);

    Livewire::actingAs($admin)
        ->test(EmailAccountsIndex::class)
        ->set('selectedAccountIds', [$accountOne->id, $accountTwo->id])
        ->call('bulkSuspend')
        ->assertHasNoErrors()
        ->set('selectedAccountIds', [$accountOne->id, $accountTwo->id])
        ->call('bulkUnsuspend')
        ->assertHasNoErrors()
        ->set('selectedAccountIds', [$accountOne->id, $accountTwo->id])
        ->set('bulkPassword', 'StrongPassword#456')
        ->call('bulkResetPassword')
        ->assertHasNoErrors()
        ->assertSet('selectedAccountIds', []);

    expect($accountOne->fresh()->suspended)->toBeFalse();
    expect($accountTwo->fresh()->suspended)->toBeFalse();

    Http::assertSentCount(6);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://cpanel.example.test:2083/execute/Email/suspend_login';
    });

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://cpanel.example.test:2083/execute/Email/unsuspend_login';
    });

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->url() === 'https://cpanel.example.test:2083/execute/Email/passwd_pop'
            && ($data['password'] ?? null) === 'StrongPassword#456';
    });
});
