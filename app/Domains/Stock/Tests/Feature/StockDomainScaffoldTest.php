<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Support\Facades\Route;

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

it('allows users with stock view-any permission to access the mobile stock scaffold routes', function (): void {
    $user = userWithStockDomainPermissions(['stock-orders.view-any']);

    actingAs($user);

    get(route('stock-orders.mobile.index'))
        ->assertSuccessful()
        ->assertSee('Track requests on the go');

    get(route('api.stock-orders.index'))
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Stock Orders API Scaffold',
        ]);
});

it('registers the admin stock templates route', function (): void {
    expect(Route::has('admin.stock-order-templates.index'))->toBeTrue();
});

it('registers the user stock templates route', function (): void {
    expect(Route::has('stock-orders.templates.browse'))->toBeTrue();
});

it('renders the mobile stock order create and show pages', function (): void {
    $user = userWithStockDomainPermissions([
        'stock-orders.view-any',
        'stock-orders.create',
        'stock-orders.view',
        'stock-orders.update',
    ]);

    $order = StockOrder::factory()->create([
        'user_id' => $user->id,
        'po_number' => 'PO-MOBILE-100',
        'status' => StockOrder::STATUS_PENDING,
    ]);

    actingAs($user);

    get(route('stock-orders.mobile.create'))
        ->assertSuccessful()
        ->assertSee('Create a new request');

    get(route('stock-orders.mobile.show', $order))
        ->assertSuccessful()
        ->assertSee('PO-MOBILE-100');

    get(route('stock-orders.mobile.edit', $order))
        ->assertSuccessful()
        ->assertSee('Update an existing request');
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
