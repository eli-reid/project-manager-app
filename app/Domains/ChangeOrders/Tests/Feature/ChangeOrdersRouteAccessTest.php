<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from admin change orders index', function (): void {
    get(route('admin.change-orders.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without change order view permission', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('admin.change-orders.index'))
        ->assertForbidden();
});

it('allows users with change-orders.view to access admin change orders index', function (): void {
    $user = userWithChangeOrderViewPermission();

    actingAs($user);

    get(route('admin.change-orders.index'))
        ->assertOk()
        ->assertSee('Admin Change Orders');
});

function userWithChangeOrderViewPermission(): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Change Order Route Role '.str()->uuid(),
        'description' => 'Role created by change order route tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 30,
    ]);

    $permissionId = Permission::query()
        ->where('resource', 'change-orders')
        ->where('action', 'view')
        ->value('id');

    if (is_string($permissionId)) {
        $role->permissions()->sync([$permissionId]);
    }

    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
