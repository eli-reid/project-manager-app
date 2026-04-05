<?php

namespace App\Core\Auth\Role\Services;

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Role\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RoleService
{
    /**
     * Get all roles, cached.
     *
     * @return Collection
     */
    public function getAllRoles()
    {
        return Cache::rememberForever('roles.all', function () {
            return Role::with('permissions')->get();
        });
    }

    /**
     * Get all permissions, cached.
     *
     * @return Collection
     */
    public function getAllPermissions()
    {
        return Cache::rememberForever('permissions.all', function () {
            return Permission::all();
        });
    }

    /**
     * Clear cached roles and permissions.
     */
    public function clearCache()
    {
        Cache::forget('roles.all');
        Cache::forget('permissions.all');
    }

    /**
     * Get permissions for a specific role, cached per role.
     *
     * @param  int  $roleId
     * @return Collection
     */
    public function getPermissionsForRole($roleId)
    {
        return Cache::rememberForever("role.{$roleId}.permissions", function () use ($roleId) {
            $role = Role::with('permissions')->find($roleId);

            return $role ? $role->permissions : collect();
        });
    }
}
