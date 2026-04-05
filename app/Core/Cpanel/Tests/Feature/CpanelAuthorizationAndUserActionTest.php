<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Settings\Facades\Settings;
use App\Core\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

function assignPermissionsToUser(User $user, array $permissionKeys): void
{
    $role = Role::query()->create([
        'name' => 'Role '.uniqid(),
        'description' => 'Temporary test role',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = [];

    foreach ($permissionKeys as $permissionKey) {
        [$resource, $action] = explode('.', $permissionKey, 2);
        $permissionIds[] = Permission::query()
            ->where('resource', $resource)
            ->where('action', $action)
            ->value('id');
    }

    $role->permissions()->sync(array_filter($permissionIds));
    $user->roles()->sync([$role->id]);
    $user->flushAuthorizationCache();
    User::bumpPermissionCacheVersion();
}

/**
 * @param  array<string, mixed>  $overrides
 */
function setCpanelSettings(array $overrides = []): void
{
    $settings = array_merge([
        'cpanel.url' => 'https://cpanel.example.test',
        'cpanel.username' => 'root',
        'cpanel.api_token' => 'token-123',
        'cpanel.domain' => 'example.test',
        'cpanel.port' => 2083,
        'cpanel.default_email_quota' => 250,
        'cpanel.verify_ssl' => 'true',
    ], $overrides);

    foreach ($settings as $key => $value) {
        Settings::set($key, $value);
    }
}

it('allows users with manage-email-accounts permission to access admin cpanel endpoints', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $permittedUser = User::factory()->create(['is_admin' => false]);
    assignPermissionsToUser($permittedUser, ['cpanel.manage-email-accounts']);

    actingAs($permittedUser)
        ->get(route('admin.cpanel.email-accounts.index'))
        ->assertStatus(422);

    $forbiddenUser = User::factory()->create(['is_admin' => false]);

    actingAs($forbiddenUser)
        ->get(route('admin.cpanel.email-accounts.index'))
        ->assertForbidden();
});

it('generates company email through admin users action route', function () {
    setCpanelSettings();

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $targetUser = User::factory()->create([
        'username' => 'jane',
        'company_email' => null,
    ]);

    actingAs($admin)
        ->post(route('admin.users.generate-company-email', $targetUser))
        ->assertRedirect()
        ->assertSessionHas('success', 'Company email generated successfully.');

    expect($targetUser->fresh()->company_email)->toBe('jane@example.test');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_pop'
            && ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test';
    });
});

it('forbids generate-company-email action without cpanel permission', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $actor = User::factory()->create(['is_admin' => false]);
    assignPermissionsToUser($actor, ['users.view']);

    $targetUser = User::factory()->create();

    actingAs($actor)
        ->post(route('admin.users.generate-company-email', $targetUser))
        ->assertForbidden();
});

it('shows the generate email action in admin users page for permitted users', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $actor = User::factory()->create(['is_admin' => false]);
    assignPermissionsToUser($actor, ['users.view', 'cpanel.manage-email-accounts']);

    User::factory()->create([
        'first_name' => 'Casey',
        'last_name' => 'Crew',
        'username' => 'casey',
        'company_email' => null,
    ]);

    actingAs($actor)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('generate-company-email');
});
