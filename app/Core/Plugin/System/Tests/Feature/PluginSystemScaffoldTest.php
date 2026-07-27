<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\PluginSystem\Models\InstalledPlugin;
use App\Core\PluginSystem\Services\PluginDiscoveryService;
use App\Core\PluginSystem\Services\PluginInstallService;
use App\Core\PluginSystem\Services\SystemPluginCatalog;
use Illuminate\Support\Str;

it('registers the plugin system admin route', function (): void {
    expect(route('admin.plugins.index', absolute: false))->toBe('/admin/plugins');
});

it('redirects guests from plugin system admin routes', function (): void {
    $this->get(route('admin.plugins.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without plugin permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.plugins.index'))
        ->assertForbidden();
});

it('allows users with plugin view permission to access the plugin system index', function (): void {
    $user = pluginSystemUserWithPermissions(['plugins.view']);

    $this->actingAs($user)
        ->get(route('admin.plugins.index'))
        ->assertSuccessful()
        ->assertSee('Plugin System')
        ->assertSee('Primary Checkpoint')
        ->assertSee('Zoom')
        ->assertSee('Cpanel');
});

it('discovers bundled plugins registered in bootstrap providers', function (): void {
    $plugins = app(PluginDiscoveryService::class)->discoverRegisteredPlugins();

    expect($plugins->pluck('slug')->all())
        ->toContain('cpanel', 'weather-api', 'zoom');

    expect($plugins->firstWhere('slug', 'cpanel'))
        ->toMatchArray([
            'trust_level' => InstalledPlugin::TRUST_FIRST_PARTY,
            'execution_mode' => InstalledPlugin::EXECUTION_IN_PROCESS_FULL,
        ]);
});

it('exposes a first-party catalog for bundled system plugins', function (): void {
    $plugins = app(SystemPluginCatalog::class)->all();

    expect($plugins)->not->toBeEmpty()
        ->and($plugins->every(fn (array $plugin): bool => $plugin['trust_level'] === InstalledPlugin::TRUST_FIRST_PARTY))->toBeTrue();
});

it('stages marketplace plugins behind a pending review security gate', function (): void {
    $plugin = app(PluginInstallService::class)->stageMarketplacePlugin([
        'slug' => 'acme-market-connector',
        'name' => 'ACME Market Connector',
        'package_name' => 'acme/market-connector',
        'provider' => 'Vendor\\AcmeMarketConnector\\Providers\\AcmeMarketConnectorServiceProvider',
        'version' => '1.2.0',
        'checksum' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'signature' => 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
        'capabilities' => ['settings', 'webhooks'],
        'required_permissions' => ['plugins.view'],
        'metadata' => [
            'source_url' => 'https://marketplace.example.test/acme-market-connector',
        ],
    ]);

    expect($plugin->status)->toBe(InstalledPlugin::STATUS_STAGED)
        ->and($plugin->security_status)->toBe(InstalledPlugin::SECURITY_PENDING_REVIEW)
        ->and($plugin->source_type)->toBe(InstalledPlugin::SOURCE_MARKETPLACE)
        ->and($plugin->trust_level)->toBe(InstalledPlugin::TRUST_REVIEWED_THIRD_PARTY)
        ->and($plugin->execution_mode)->toBe(InstalledPlugin::EXECUTION_IN_PROCESS_LIMITED)
        ->and($plugin->manifest_checksum)->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
});

/**
 * @param  array<int, string>  $permissions
 */
function pluginSystemUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Plugin System Test Role '.str()->uuid(),
        'description' => 'Role created by plugin system scaffold tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 35,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            $resource = Str::before($permission, '.');
            $action = Str::after($permission, '.');

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
