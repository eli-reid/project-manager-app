<?php

namespace App\Core\Identity\Services;

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

class UserAuthorizationSnapshotService
{
    private const SESSION_PERMISSION_SNAPSHOT_PREFIX = 'auth.permission_snapshot.';

    private const PERMISSION_CACHE_VERSION_KEY = 'auth.permission_cache.version';

    /**
     * @param  array{version:string, permission_keys:array<int, string>, has_admin_role:bool}|null  $inMemorySnapshot
     * @return array{version:string, permission_keys:array<int, string>, has_admin_role:bool}
     */
    public function resolve(User $user, ?array $inMemorySnapshot = null): array
    {
        if ($inMemorySnapshot !== null) {
            return $inMemorySnapshot;
        }

        $cacheVersion = $this->permissionCacheVersion();

        if ($this->hasSessionContext()) {
            $snapshot = session()->get($this->sessionSnapshotKey($user));

            if (is_array($snapshot)
                && isset($snapshot['version'], $snapshot['permission_keys'], $snapshot['has_admin_role'])
                && $snapshot['version'] === $cacheVersion
                && is_array($snapshot['permission_keys'])
                && is_bool($snapshot['has_admin_role'])) {
                /** @var array{version:string, permission_keys:array<int, string>, has_admin_role:bool} $snapshot */
                return $snapshot;
            }
        }

        $snapshot = $this->buildAuthorizationSnapshot($user, $cacheVersion);

        if ($this->hasSessionContext()) {
            session()->put($this->sessionSnapshotKey($user), $snapshot);
        }

        return $snapshot;
    }

    public function flush(User $user): void
    {
        if ($this->hasSessionContext()) {
            session()->forget($this->sessionSnapshotKey($user));
        }
    }

    public function bumpPermissionCacheVersion(): void
    {
        try {
            Cache::forever(self::PERMISSION_CACHE_VERSION_KEY, now()->format('Uu'));
        } catch (Throwable) {
            // Ignore cache-store availability errors and fall back to request-scoped caching.
        }
    }

    /**
     * @return array{version:string, permission_keys:array<int, string>, has_admin_role:bool}
     */
    private function buildAuthorizationSnapshot(User $user, string $cacheVersion): array
    {
        $roles = $user->roles()
            ->where('roles.is_active', true)
            ->with(['permissions:id,resource,action'])
            ->get(['roles.id', 'roles.name', 'roles.built_in']);

        $permissionKeys = $roles
            ->flatMap(function (Role $role): array {
                return $role->permissions
                    ->map(fn (Permission $permission): string => $permission->resource.'.'.$permission->action)
                    ->all();
            })
            ->unique()
            ->values()
            ->all();

        $hasAdminRole = $roles->contains(
            fn (Role $role): bool => (bool) $role->built_in && strcasecmp($role->name, Role::BUILT_IN_ADMIN) === 0
        );

        return [
            'version' => $cacheVersion,
            'permission_keys' => $permissionKeys,
            'has_admin_role' => $hasAdminRole,
        ];
    }

    private function sessionSnapshotKey(User $user): string
    {
        return self::SESSION_PERMISSION_SNAPSHOT_PREFIX.(string) $user->getAuthIdentifier();
    }

    private function hasSessionContext(): bool
    {
        if (! app()->bound('session')) {
            return false;
        }

        try {
            session()->getName();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function permissionCacheVersion(): string
    {
        try {
            return (string) Cache::get(self::PERMISSION_CACHE_VERSION_KEY, '1');
        } catch (Throwable) {
            return '1';
        }
    }
}
