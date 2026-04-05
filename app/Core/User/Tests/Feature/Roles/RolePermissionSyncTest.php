<?php

use App\Core\User\Contracts\PermissionRegistryContract;
use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Core\User\Services\PermissionRegistry;

it('binds permission registry contract to concrete implementation', function () {
    $registry = app(PermissionRegistryContract::class);

    expect($registry)->toBeInstanceOf(PermissionRegistry::class);
});

it('synchronizes registered domain permissions and built-in roles', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    expect(Role::query()->where('name', Role::BUILT_IN_ADMIN)->where('built_in', true)->exists())->toBeTrue()
        ->and(Role::query()->where('name', Role::BUILT_IN_USER)->where('built_in', true)->exists())->toBeTrue()
        ->and(Permission::query()->where('resource', 'settings')->where('action', 'view')->exists())->toBeTrue()
        ->and(Permission::query()->where('resource', 'users')->where('action', 'view')->exists())->toBeTrue()
        ->and(Permission::query()->where('resource', 'scheduler')->where('action', 'view')->exists())->toBeTrue();
});

it('supports registering domain permissions before synchronization', function () {
    $registry = app(PermissionRegistryContract::class);
    $registry->registerPermissions([
        [
            'resource' => 'reports',
            'action' => 'export',
            'label' => 'Export Reports',
            'description' => 'Export reports to file formats',
            'built_in_roles' => ['User'],
        ],
    ]);

    app(DomainPermissionSynchronizer::class)->sync();

    expect(Permission::query()->where('resource', 'reports')->where('action', 'export')->exists())->toBeTrue();
});

it('prevents disabling or deleting built-in roles', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $adminRole = Role::query()->where('name', Role::BUILT_IN_ADMIN)->firstOrFail();

    expect($adminRole->toggleStatus())->toBeFalse();

    $adminRole->refresh();
    expect($adminRole->is_active)->toBeTrue();

    expect($adminRole->delete())->toBeFalse()
        ->and(Role::query()->whereKey($adminRole->id)->exists())->toBeTrue();
});
