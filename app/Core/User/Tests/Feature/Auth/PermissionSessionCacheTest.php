<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use Illuminate\Support\Facades\DB;

it('caches permission checks in session between user instances', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $permissionId = Permission::query()
        ->where('resource', 'users')
        ->where('action', 'view')
        ->value('id');

    expect($permissionId)->not->toBeNull();

    $role = Role::query()->create([
        'name' => 'Permission Cache Test Role '.str()->uuid(),
        'description' => 'Role for permission session cache tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $role->permissions()->sync([$permissionId]);

    $user = User::factory()->create(['is_admin' => false]);
    $user->roles()->sync([$role->id]);

    User::bumpPermissionCacheVersion();
    session()->start();

    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    expect($user->fresh()->hasPermission('users.view'))->toBeTrue();

    $firstPermissionQueryCount = permissionAuthorizationQueryCount($connection->getQueryLog());

    $connection->flushQueryLog();

    expect($user->fresh()->hasPermission('users.view'))->toBeTrue();

    $secondPermissionQueryCount = permissionAuthorizationQueryCount($connection->getQueryLog());

    expect($firstPermissionQueryCount)->toBeGreaterThan(0)
        ->and($secondPermissionQueryCount)->toBe(0);
});

it('refreshes session permission cache after cache version bump', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $permissionId = Permission::query()
        ->where('resource', 'users')
        ->where('action', 'view')
        ->value('id');

    expect($permissionId)->not->toBeNull();

    $role = Role::query()->create([
        'name' => 'Permission Cache Refresh Role '.str()->uuid(),
        'description' => 'Role for permission refresh tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    $user->roles()->sync([$role->id]);

    User::bumpPermissionCacheVersion();
    session()->start();

    expect($user->fresh()->hasPermission('users.view'))->toBeFalse();

    $role->permissions()->sync([$permissionId]);

    expect($user->fresh()->hasPermission('users.view'))->toBeFalse();

    User::bumpPermissionCacheVersion();

    expect($user->fresh()->hasPermission('users.view'))->toBeTrue();
});

/**
 * @param  array<int, array<string, mixed>>  $queryLog
 */
function permissionAuthorizationQueryCount(array $queryLog): int
{
    return collect($queryLog)
        ->pluck('query')
        ->filter(fn (mixed $query): bool => is_string($query))
        ->map(fn (string $query): string => strtolower($query))
        ->filter(function (string $query): bool {
            return str_contains($query, ' from "roles"')
                || str_contains($query, ' from "permissions"')
                || str_contains($query, ' from `roles`')
                || str_contains($query, ' from `permissions`');
        })
        ->count();
}
