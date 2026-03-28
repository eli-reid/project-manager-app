<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from stock scaffold routes', function (): void {
    get(route('admin.stock-orders.index'))
        ->assertRedirect(route('login'));

    get(route('stock-orders.index'))
        ->assertRedirect(route('login'));

    get(route('stock-orders.mobile.index'))
        ->assertRedirect(route('login'));

    get(route('api.stock-orders.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without stock permissions', function (): void {
    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    actingAs($user);

    get(route('admin.stock-orders.index'))
        ->assertForbidden();

    get(route('stock-orders.index'))
        ->assertForbidden();

    get(route('stock-orders.mobile.index'))
        ->assertForbidden();

    get(route('api.stock-orders.index'))
        ->assertForbidden();
});

it('allows users with stock view-any permission to access all phase 0 scaffold routes', function (): void {
    $user = userWithStockDomainPermissions(['stock-orders.view-any']);

    actingAs($user);

    get(route('admin.stock-orders.index'))
        ->assertSuccessful()
        ->assertSee('Stock Orders Admin (Scaffold)');

    get(route('stock-orders.index'))
        ->assertSuccessful()
        ->assertSee('My Stock Orders');

    get(route('stock-orders.mobile.index'))
        ->assertSuccessful()
        ->assertSee('Stock Orders Mobile (Scaffold)');

    get(route('api.stock-orders.index'))
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Stock Orders API Scaffold',
        ]);
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithStockDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Stock Test Role '.str()->uuid(),
        'description' => 'Role for stock domain tests',
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
