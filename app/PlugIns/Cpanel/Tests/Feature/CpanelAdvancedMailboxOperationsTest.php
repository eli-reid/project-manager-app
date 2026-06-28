<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

function grantCpanelPermission(User $user): void
{
    $role = Role::query()->create([
        'name' => 'Cpanel Ops '.uniqid(),
        'description' => 'Temporary cPanel ops role',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionId = Permission::query()
        ->where('resource', 'cpanel')
        ->where('action', 'manage-email-accounts')
        ->value('id');

    $role->permissions()->sync([$permissionId]);
    $user->roles()->sync([$role->id]);
}

beforeEach(function () {
    app(DomainPermissionSynchronizer::class)->sync();

    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.port', '2083');
    Settings::set('cpanel.verify_ssl', 'true');
    Settings::set('cpanel.default_email_quota', '250');
});

it('resets mailbox password through admin endpoint', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    grantCpanelPermission($user);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.reset-password', ['email' => 'john@example.test']), [
            'password' => 'StrongPassword#123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/passwd_pop'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['password'] ?? null) === 'StrongPassword#123';
    });
});

it('suspends and unsuspends mailbox through admin endpoints', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/suspend_login' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
        'https://cpanel.example.test:2083/execute/Email/unsuspend_login' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    grantCpanelPermission($user);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.suspend', ['email' => 'john@example.test']))
        ->assertOk()
        ->assertJsonPath('success', true);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.unsuspend', ['email' => 'john@example.test']))
        ->assertOk()
        ->assertJsonPath('success', true);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/suspend_login'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test';
    });

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/unsuspend_login'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test';
    });
});

it('manages mailbox forwarders through admin endpoints', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/list_forwarders*' => Http::response([
            'status' => 1,
            'data' => [
                [
                    'email' => 'john@example.test',
                    'forward' => 'ops@example.net',
                ],
            ],
        ]),
        'https://cpanel.example.test:2083/execute/Email/add_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
        'https://cpanel.example.test:2083/execute/Email/delete_forwarder' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    grantCpanelPermission($user);

    actingAs($user)
        ->getJson(route('admin.cpanel.email-accounts.forwarders.index', ['email' => 'john@example.test']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 1);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.forwarders.store', ['email' => 'john@example.test']), [
            'forward_to' => 'ops@example.net',
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    actingAs($user)
        ->deleteJson(route('admin.cpanel.email-accounts.forwarders.destroy', ['email' => 'john@example.test']), [
            'forward_to' => 'ops@example.net',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://cpanel.example.test:2083/execute/Email/list_forwarders');
    });

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_forwarder'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['fwdemail'] ?? null) === 'ops@example.net';
    });

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/delete_forwarder'
            && ($data['email'] ?? null) === 'john'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['fwdemail'] ?? null) === 'ops@example.net';
    });
});

it('forbids advanced mailbox operation endpoints without permission', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.suspend', ['email' => 'john@example.test']))
        ->assertForbidden();
});

it('validates advanced mailbox operation requests', function () {
    $user = User::factory()->create(['is_admin' => false]);
    grantCpanelPermission($user);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.reset-password', ['email' => 'not-an-email']), [
            'password' => 'short',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.suspend', ['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    actingAs($user)
        ->getJson(route('admin.cpanel.email-accounts.forwarders.index', ['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    actingAs($user)
        ->postJson(route('admin.cpanel.email-accounts.forwarders.store', ['email' => 'john@example.test']), [
            'forward_to' => 'john@example.test',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['forward_to']);

    actingAs($user)
        ->deleteJson(route('admin.cpanel.email-accounts.forwarders.destroy', ['email' => 'john@example.test']), [
            'forward_to' => 'invalid-destination',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['forward_to']);
});
