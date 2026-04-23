<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;

it('allows users with change-orders.view to view any change order', function (): void {
    $user = userWithChangeOrderPermissions(['change-orders.view']);
    $changeOrder = ChangeOrder::factory()->create();

    expect($user->can('viewAny', ChangeOrder::class))->toBeTrue()
        ->and($user->can('view', $changeOrder))->toBeTrue();
});

it('allows users with change-orders.create to create change orders', function (): void {
    $user = userWithChangeOrderPermissions(['change-orders.create']);

    expect($user->can('create', ChangeOrder::class))->toBeTrue();
});

it('allows users with change-orders.edit to update and submit draft change orders', function (): void {
    $user = userWithChangeOrderPermissions(['change-orders.edit']);
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_DRAFT,
    ]);

    expect($user->can('update', $changeOrder))->toBeTrue()
        ->and($user->can('submit', $changeOrder))->toBeTrue();
});

it('allows users with change-orders.approve to approve and reject submitted change orders', function (): void {
    $user = userWithChangeOrderPermissions(['change-orders.approve']);
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_SUBMITTED,
    ]);

    expect($user->can('approve', $changeOrder))->toBeTrue()
        ->and($user->can('reject', $changeOrder))->toBeTrue();
});

it('denies users without permissions', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_DRAFT,
    ]);

    expect($user->can('viewAny', ChangeOrder::class))->toBeFalse()
        ->and($user->can('create', ChangeOrder::class))->toBeFalse()
        ->and($user->can('update', $changeOrder))->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithChangeOrderPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Change Order Policy Role '.str()->uuid(),
        'description' => 'Role created by change order policy tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 30,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            $permissionId = Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');

            return is_string($permissionId) ? $permissionId : null;
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
