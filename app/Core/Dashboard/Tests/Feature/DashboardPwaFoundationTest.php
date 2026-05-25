<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;

it('redirects guests away from dashboard routes', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login', absolute: false));

    $this->get(route('mobile.dashboard'))
        ->assertRedirect(route('login', absolute: false));
});

it('allows unverified users to access dashboard routes', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk();
});

it('renders pwa metadata on the dashboard shell', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('rel="manifest" href="/manifest.json"', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee('viewport-fit=cover', false);
});

it('avoids caching navigation html in the service worker to prevent stale csrf tokens', function (): void {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    expect($serviceWorker)
        ->toBeString()
        ->toContain("event.request.mode === 'navigate'")
        ->toContain('caches.match(OFFLINE_URL)')
        ->not->toContain('cache.put(event.request');
});

it('renders the mobile dashboard shell for authenticated users', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk()
        ->assertSee('data-pwa-mobile-nav', false)
        ->assertSee('data-pwa-install-action', false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Log out')
        ->assertSee('Profile Settings')
        ->assertSee('Offline mode')
        ->assertSee('Install App')
        ->assertSee('Dashboard');
});

it('redirects authenticated mobile browsers from dashboard to mobile dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1')
        ->get(route('dashboard'))
        ->assertRedirect(route('mobile.dashboard', absolute: false));
});

it('keeps authenticated desktop browsers on dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard');
});

it('renders project documents widget on mobile dashboard when global project documents exist', function (): void {
    $user = dashboardUserWithPermissions([
        'documents.view',
        'projects.view',
    ]);

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
        'is_active' => true,
        'status' => 'in_progress',
    ]);

    Document::factory()->projectOwned()->create([
        'title' => 'Issued For Construction Set',
        'owner_id' => $project->id,
        'visibility' => Document::VISIBILITY_GLOBAL,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk()
        ->assertSee('Project Documents')
        ->assertSee('Issued For Construction Set');
});

/**
 * @param  array<int, string>  $permissions
 */
function dashboardUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Dashboard Test Role '.str()->uuid(),
        'description' => 'Role for dashboard feature tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

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
