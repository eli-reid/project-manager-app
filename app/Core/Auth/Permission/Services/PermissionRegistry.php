<?php

namespace App\Core\Auth\Permission\Services;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use Illuminate\Support\Facades\Log;

class PermissionRegistry implements PermissionRegistryContract
{
    /**
     * @var array<string, array{resource:string, action:string, label:string, description:string}>
     */
    private array $permissions = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $builtInRolePermissions = [];

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    public function registerPermissions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $resource = trim((string) ($definition['resource'] ?? ''));
            $action = trim((string) ($definition['action'] ?? ''));

            if ($resource === '' || $action === '') {
                continue;
            }

            $key = $resource.'.'.$action;

            if (array_key_exists($key, $this->permissions)) {
                Log::warning('PermissionRegistry: duplicate key ignored during registerPermissions.', [
                    'key' => $key,
                    'existing_label' => $this->permissions[$key]['label'],
                ]);

                continue;
            }

            $this->permissions[$key] = [
                'resource' => $resource,
                'action' => $action,
                'label' => (string) ($definition['label'] ?? $this->defaultLabel($resource, $action)),
                'description' => (string) ($definition['description'] ?? ''),
            ];

            $builtInRoles = $definition['built_in_roles'] ?? [];

            if (is_array($builtInRoles)) {
                foreach ($builtInRoles as $roleName) {
                    $this->grantToBuiltInRole((string) $roleName, [$key]);
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    public function grantToBuiltInRole(string $roleName, array $permissionKeys): void
    {
        $roleName = trim($roleName);

        if ($roleName === '') {
            return;
        }

        $existing = $this->builtInRolePermissions[$roleName] ?? [];
        $this->builtInRolePermissions[$roleName] = array_values(array_unique(array_merge($existing, $permissionKeys)));
    }

    /**
     * @return array<int, array{resource:string, action:string, label:string, description:string}>
     */
    public function permissions(): array
    {
        return array_values($this->permissions);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function builtInRolePermissions(): array
    {
        return $this->builtInRolePermissions;
    }

    private function defaultLabel(string $resource, string $action): string
    {
        return str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value();
    }
}
