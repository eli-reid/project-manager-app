<?php

namespace App\Core\User\Services;

use App\Core\User\Contracts\PermissionRegistryContract;
use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DomainPermissionSynchronizer
{
    private const CACHE_KEY_HASH = 'permissions.domain-definitions.hash';

    public function __construct(
        private readonly PermissionRegistryContract $registry
    ) {}

    public function syncIfChanged(): int
    {
        if (! $this->isSchemaReady()) {
            return 0;
        }

        if (! $this->isCacheStoreReady()) {
            return 0;
        }

        $currentHash = $this->definitionsHash($this->registry);
        $lastHash = (string) Cache::get(self::CACHE_KEY_HASH, '');

        if ($currentHash === $lastHash) {
            return 0;
        }

        $changes = $this->sync();
        Cache::forever(self::CACHE_KEY_HASH, $currentHash);

        return $changes;
    }

    public function sync(): int
    {
        if (! $this->isSchemaReady()) {
            return 0;
        }

        $permissionDefinitions = $this->registry->permissions();

        if ($permissionDefinitions === []) {
            return 0;
        }

        $now = now();
        $permissionDefinitions = collect($permissionDefinitions)
            ->map(function (array $definition) use ($now): array {
                return [
                    ...$definition,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        Permission::query()->upsert(
            $permissionDefinitions,
            ['resource', 'action'],
            ['label', 'description', 'updated_at']
        );

        $this->ensureBuiltInRoles($this->registry->builtInRolePermissions());
        $this->flushRoleCaches();
        User::bumpPermissionCacheVersion();

        return count($permissionDefinitions);
    }

    /**
     * @param  array<string, array<int, string>>  $builtInRolePermissions
     */
    private function ensureBuiltInRoles(array $builtInRolePermissions): void
    {
        $builtInRoles = [
            'Admin' => [
                'description' => 'Built-in administrator role',
                'access_level' => 100,
            ],
            'User' => [
                'description' => 'Built-in standard user role',
                'access_level' => 10,
            ],
        ];

        foreach ($builtInRoles as $name => $metadata) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $metadata['description'],
                    'is_active' => true,
                    'built_in' => true,
                    'access_level' => $metadata['access_level'],
                ]
            );

            $permissionKeys = $builtInRolePermissions[$name] ?? [];

            if ($name === 'Admin') {
                $permissionIds = Permission::query()->pluck('id')->all();
            } else {
                $permissionIds = $this->permissionIdsFromKeys($permissionKeys);
            }

            $role->permissions()->sync($permissionIds);
        }
    }

    /**
     * @param  array<int, string>  $permissionKeys
     * @return array<int, string>
     */
    private function permissionIdsFromKeys(array $permissionKeys): array
    {
        if ($permissionKeys === []) {
            return [];
        }

        $keys = collect($permissionKeys)
            ->filter(fn ($key) => str_contains((string) $key, '.'))
            ->values();

        if ($keys->isEmpty()) {
            return [];
        }

        $pairs = $keys->map(function (string $key): array {
            [$resource, $action] = explode('.', $key, 2);

            return [
                'resource' => $resource,
                'action' => $action,
            ];
        });

        return Permission::query()
            ->where(function ($query) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $query->orWhere(function ($nested) use ($pair): void {
                        $nested->where('resource', $pair['resource'])
                            ->where('action', $pair['action']);
                    });
                }
            })
            ->pluck('id')
            ->all();
    }

    private function flushRoleCaches(): void
    {
        if (! $this->isCacheStoreReady()) {
            return;
        }

        Cache::forget('roles.all');
        Cache::forget('permissions.all');

        if (Schema::hasTable('roles')) {
            DB::table('roles')->select('id')->orderBy('id')->chunk(200, function ($roles): void {
                foreach ($roles as $role) {
                    Cache::forget("role.{$role->id}.permissions");
                }
            });
        }
    }

    private function isSchemaReady(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('roles')
            && Schema::hasTable('role_permissions');
    }

    private function definitionsHash(PermissionRegistryContract $registry): string
    {
        $payload = json_encode([
            'permissions' => $registry->permissions(),
            'built_in_role_permissions' => $registry->builtInRolePermissions(),
        ]);

        return hash('sha256', $payload ?: '');
    }

    private function isCacheStoreReady(): bool
    {
        $defaultStore = (string) config('cache.default');

        if ($defaultStore !== 'database') {
            return true;
        }

        $cacheTable = (string) config('cache.stores.database.table', 'cache');

        return Schema::hasTable($cacheTable);
    }
}
